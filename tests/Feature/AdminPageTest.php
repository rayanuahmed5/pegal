<?php

namespace Tests\Feature;

use App\Domain\BookingStatus;
use App\Domain\Role;
use App\Models\Booking;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// who may open the administration and what it shows and does
class AdminPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['name' => 'Ruth Admin', 'role' => Role::Admin]);
    }

    private function supplier(): Supplier
    {
        return Supplier::create(['name' => 'Test Consulting']);
    }

    public static function otherRoles(): array
    {
        return [
            'consultant' => [Role::Consultant],
            'project lead' => [Role::ProjectLead],
            'purchasing' => [Role::Purchasing],
            'finance' => [Role::Finance],
        ];
    }

    private function newUser(array $changes = []): array
    {
        return array_merge([
            'name' => 'Kofi Boateng',
            'email' => 'kofi@test.test',
            'role' => 'finance',
            'supplier_id' => null,
            'password' => 'a-long-password',
            'password_confirmation' => 'a-long-password',
        ], $changes);
    }

    // access

    public function test_a_guest_is_sent_to_the_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->post('/admin/users', $this->newUser())->assertRedirect('/login');
    }

    #[DataProvider('otherRoles')]
    public function test_everybody_else_gets_a_403(Role $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/admin/users/create')->assertForbidden();
        $this->actingAs($user)->post('/admin/users', $this->newUser())->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'kofi@test.test']);
    }

    public function test_the_administrator_does_not_see_the_work_pages(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/orders')->assertForbidden();
        $this->actingAs($admin)->get('/proof-of-work')->assertForbidden();
        $this->actingAs($admin)->get('/bookings')->assertForbidden();
    }

    public function test_the_home_page_links_the_administrator_to_the_dashboard(): void
    {
        $this->actingAs($this->admin())->get('/')
            ->assertOk()
            ->assertSee('Administrator')
            ->assertSee('/admin');
    }

    // dashboard

    public function test_the_dashboard_shows_the_users_their_roles_and_person_days(): void
    {
        $supplier = $this->supplier();
        $ama = User::factory()->create(['name' => 'Ama Mensah', 'role' => Role::Consultant, 'supplier_id' => $supplier->id]);
        $order = Order::create([
            'number' => '4500000001',
            'title' => 'Test order',
            'supplier_id' => $supplier->id,
            'cost_center' => 'CC-1',
            'budget_pd' => 120,
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-12-31',
            'reorder_lead_weeks' => 4,
        ]);
        $order->consultants()->attach($ama);
        Booking::create([
            'order_id' => $order->id,
            'user_id' => $ama->id,
            'worked_on' => '2026-10-05',
            'hours' => 12,
            'description' => 'Secret details of the work',
            'status' => BookingStatus::Approved,
        ]);

        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Users in total')
            ->assertSee('Ama Mensah')
            ->assertSee('Consultant')
            ->assertSee('Test Consulting')
            ->assertSee('120')
            ->assertSee('1.5')
            ->assertDontSee('Secret details of the work');
    }

    public function test_the_dashboard_counts_the_users(): void
    {
        $admin = $this->admin();
        User::factory()->count(2)->create(['role' => Role::Finance]);

        $response = $this->actingAs($admin)->get('/admin')->assertOk();

        // 1 admin + 2 finance
        $this->assertSame(3, $response->viewData('total'));
        $this->assertSame(2, $response->viewData('byRole')['finance']);
    }

    // adding users

    public function test_the_administrator_can_add_a_finance_user_who_can_sign_in(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/users', $this->newUser())
            ->assertRedirect('/admin')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', ['email' => 'kofi@test.test', 'role' => 'finance', 'supplier_id' => null]);

        // a fresh browser session, signing in with what the administrator typed
        $this->post('/logout');
        $this->post('/login', ['email' => 'kofi@test.test', 'password' => 'a-long-password'])->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_the_administrator_can_add_a_consultant_with_a_supplier(): void
    {
        $supplier = $this->supplier();

        $this->actingAs($this->admin())
            ->post('/admin/users', $this->newUser(['role' => 'consultant', 'supplier_id' => $supplier->id]))
            ->assertRedirect('/admin');

        $this->assertDatabaseHas('users', ['email' => 'kofi@test.test', 'role' => 'consultant', 'supplier_id' => $supplier->id]);
    }

    public function test_a_consultant_without_a_supplier_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->from('/admin/users/create')
            ->post('/admin/users', $this->newUser(['role' => 'consultant']))
            ->assertSessionHasErrors('supplier_id');

        $this->assertDatabaseMissing('users', ['email' => 'kofi@test.test']);
    }

    public function test_an_email_that_is_taken_is_refused(): void
    {
        User::factory()->create(['email' => 'kofi@test.test']);

        $this->actingAs($this->admin())
            ->post('/admin/users', $this->newUser())
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', 'kofi@test.test')->count());
    }

    public function test_a_short_password_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/users', $this->newUser(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'kofi@test.test']);
    }

    public function test_a_password_that_is_not_repeated_the_same_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/users', $this->newUser(['password_confirmation' => 'something-else']))
            ->assertSessionHasErrors('password');
    }

    public function test_a_made_up_role_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/users', $this->newUser(['role' => 'superuser']))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'kofi@test.test']);
    }
}
