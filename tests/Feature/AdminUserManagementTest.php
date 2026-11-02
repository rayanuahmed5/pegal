<?php

namespace Tests\Feature;

use App\Domain\BookingStatus;
use App\Domain\Role;
use App\Models\Booking;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// the administrator edits accounts, resets passwords and deletes accounts, nobody else may
class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['name' => 'Ruth Admin', 'role' => Role::Admin]);
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

    // a consultant with one booking, the case where deleting must be refused
    private function consultantWithBooking(): User
    {
        $supplier = Supplier::create(['name' => 'Test Consulting']);
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
        Booking::create([
            'order_id' => $order->id,
            'user_id' => $ama->id,
            'worked_on' => '2026-10-05',
            'hours' => 8,
            'description' => 'Work',
            'status' => BookingStatus::Approved,
        ]);

        return $ama;
    }

    // access

    #[DataProvider('otherRoles')]
    public function test_nobody_but_the_administrator_can_manage_accounts(Role $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $victim = User::factory()->create(['name' => 'Kofi Boateng', 'role' => Role::Finance]);

        $this->actingAs($user)->get("/admin/users/{$victim->id}/edit")->assertForbidden();
        $this->actingAs($user)->put("/admin/users/{$victim->id}", ['name' => 'Hacked', 'email' => 'x@test.test', 'role' => 'admin'])->assertForbidden();
        $this->actingAs($user)->put("/admin/users/{$victim->id}/password", ['password' => 'taken-over-now', 'password_confirmation' => 'taken-over-now'])->assertForbidden();
        $this->actingAs($user)->delete("/admin/users/{$victim->id}")->assertForbidden();

        $victim->refresh();
        $this->assertSame('Kofi Boateng', $victim->name);
        $this->assertSame(Role::Finance, $victim->role);
        $this->assertFalse(Hash::check('taken-over-now', $victim->password));
    }

    public function test_the_dashboard_links_to_the_edit_page(): void
    {
        $admin = $this->admin();
        $kofi = User::factory()->create(['role' => Role::Finance]);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee("/admin/users/{$kofi->id}/edit");
        $this->actingAs($admin)->get("/admin/users/{$kofi->id}/edit")->assertOk()->assertSee($kofi->email);
    }

    // editing

    public function test_the_administrator_changes_name_email_and_role(): void
    {
        $kofi = User::factory()->create(['name' => 'Kofi', 'role' => Role::Finance]);

        $this->actingAs($this->admin())
            ->put("/admin/users/{$kofi->id}", ['name' => 'Kofi Boateng', 'email' => 'kofi.b@test.test', 'role' => 'purchasing'])
            ->assertRedirect('/admin')
            ->assertSessionHas('status');

        $kofi->refresh();
        $this->assertSame('Kofi Boateng', $kofi->name);
        $this->assertSame('kofi.b@test.test', $kofi->email);
        $this->assertSame(Role::Purchasing, $kofi->role);
    }

    public function test_an_email_already_used_by_someone_else_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@test.test']);
        $kofi = User::factory()->create(['role' => Role::Finance]);

        $this->actingAs($this->admin())
            ->put("/admin/users/{$kofi->id}", ['name' => 'Kofi', 'email' => 'taken@test.test', 'role' => 'finance'])
            ->assertSessionHasErrors('email');
    }

    public function test_the_administrator_cannot_change_their_own_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put("/admin/users/{$admin->id}", ['name' => $admin->name, 'email' => $admin->email, 'role' => 'finance'])
            ->assertSessionHasErrors(['profile' => 'You cannot change your own role. Ask another administrator.']);

        $this->assertSame(Role::Admin, $admin->fresh()->role);
    }

    // password reset

    public function test_the_administrator_resets_a_password(): void
    {
        $kofi = User::factory()->create(['role' => Role::Finance]);

        $this->actingAs($this->admin())
            ->put("/admin/users/{$kofi->id}/password", ['password' => 'a-fresh-password', 'password_confirmation' => 'a-fresh-password'])
            ->assertRedirect("/admin/users/{$kofi->id}/edit")
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('a-fresh-password', $kofi->fresh()->password));
    }

    public function test_a_short_reset_password_is_rejected(): void
    {
        $kofi = User::factory()->create(['role' => Role::Finance]);

        $this->actingAs($this->admin())
            ->put("/admin/users/{$kofi->id}/password", ['password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $kofi->fresh()->password));
    }

    // deleting

    public function test_the_administrator_deletes_an_account_without_bookings(): void
    {
        $kofi = User::factory()->create(['role' => Role::Finance]);

        $this->actingAs($this->admin())
            ->delete("/admin/users/{$kofi->id}")
            ->assertRedirect('/admin')
            ->assertSessionHas('status');

        $this->assertModelMissing($kofi);
    }

    public function test_an_account_with_bookings_cannot_be_deleted(): void
    {
        $ama = $this->consultantWithBooking();

        $this->actingAs($this->admin())
            ->delete("/admin/users/{$ama->id}")
            ->assertSessionHasErrors('delete');

        $this->assertModelExists($ama);
    }

    public function test_the_administrator_cannot_delete_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete("/admin/users/{$admin->id}")
            ->assertSessionHasErrors(['delete' => 'You cannot delete your own account.']);

        $this->assertModelExists($admin);
    }
}
