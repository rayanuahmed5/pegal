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

// today is Monday 2 Nov 2026. The order has 10 PD, 5 PD were booked on 12 to 16 Oct, so it runs
// out on Fri 27 Nov and about 6 PD are missing until the order ends on 31 Dec
class FollowUpProposalPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-02');
    }

    private function order(string $number, array $days): Order
    {
        $supplier = Supplier::firstOrCreate(['name' => 'Test Consulting']);
        $consultant = User::factory()->create(['name' => 'Secret Consultant', 'role' => Role::Consultant, 'supplier_id' => $supplier->id]);
        $order = Order::create([
            'number' => $number,
            'title' => 'Order '.$number,
            'supplier_id' => $supplier->id,
            'cost_center' => 'CC-7',
            'budget_pd' => 10,
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-12-31',
            'reorder_lead_weeks' => 4,
        ]);
        $order->consultants()->attach($consultant);

        foreach ($days as $day) {
            Booking::create([
                'order_id' => $order->id,
                'user_id' => $consultant->id,
                'worked_on' => $day,
                'hours' => 8,
                'description' => 'Work',
                'status' => BookingStatus::Approved,
            ]);
        }

        return $order;
    }

    private function dueOrder(): Order
    {
        return $this->order('4500000009', ['2026-10-12', '2026-10-13', '2026-10-14', '2026-10-15', '2026-10-16']);
    }

    private function user(Role $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public static function allowedRoles(): array
    {
        return [
            'project lead' => [Role::ProjectLead],
            'purchasing' => [Role::Purchasing],
        ];
    }

    #[DataProvider('allowedRoles')]
    public function test_project_lead_and_purchasing_get_the_draft(Role $role): void
    {
        $order = $this->dueOrder();

        $this->actingAs($this->user($role))
            ->get("/follow-ups/{$order->id}/proposal")
            ->assertOk()
            ->assertSee('Follow-up order proposal')
            ->assertSee('Scope: 6 PD (person-days)')
            ->assertSee('Period: 30 Nov 2026 (week 49) to 31 Dec 2026 (week 53)')
            ->assertSee('Place the order by: 30 Oct 2026 (week 44)')
            ->assertSee('this date has passed, please order now')
            ->assertSee('CC-7')
            ->assertSee('Test Consulting');
    }

    public function test_the_draft_shows_no_person(): void
    {
        $order = $this->dueOrder();

        $this->actingAs($this->user(Role::Purchasing))
            ->get("/follow-ups/{$order->id}/proposal")
            ->assertOk()
            ->assertDontSee('Secret Consultant');
    }

    public function test_the_follow_up_list_links_to_the_draft(): void
    {
        $order = $this->dueOrder();

        $this->actingAs($this->user(Role::Purchasing))
            ->get('/follow-ups')
            ->assertOk()
            ->assertSee("/follow-ups/{$order->id}/proposal");
    }

    public function test_an_order_that_needs_no_follow_up_sends_you_back_with_a_message(): void
    {
        $order = $this->order('4500000005', []);

        $this->actingAs($this->user(Role::Purchasing))
            ->get("/follow-ups/{$order->id}/proposal")
            ->assertRedirect('/follow-ups')
            ->assertSessionHasErrors('proposal');
    }

    public function test_finance_and_consultants_get_a_403(): void
    {
        $order = $this->dueOrder();

        foreach ([Role::Finance, Role::Consultant, Role::Admin] as $role) {
            $this->actingAs($this->user($role))
                ->get("/follow-ups/{$order->id}/proposal")
                ->assertForbidden();
        }
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $order = $this->dueOrder();

        $this->get("/follow-ups/{$order->id}/proposal")->assertRedirect('/login');
    }
}
