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

// today is Monday 2 Nov 2026 here. With no holidays in the test database:
//  - 4500000009: 5 PD of 10 booked in the last 4 weeks, runs out 27 Nov, reorder date 30 Oct -> overdue by 3 days
//  - 4500000003: 4 PD of 10 booked, runs out 11 Dec, reorder date 13 Nov -> in 11 days
//  - 4500000005: nothing booked, budget lasts until the order ends -> not on the list
class FollowUpPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-02');
    }

    private function orderWithBookedDays(string $number, array $days): void
    {
        $supplier = Supplier::firstOrCreate(['name' => 'Test Consulting']);
        $consultant = User::factory()->create(['role' => Role::Consultant, 'supplier_id' => $supplier->id]);
        $order = Order::create([
            'number' => $number,
            'title' => 'Order '.$number,
            'supplier_id' => $supplier->id,
            'cost_center' => 'CC-1',
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
    }

    private function threeOrders(): void
    {
        $this->orderWithBookedDays('4500000009', ['2026-10-12', '2026-10-13', '2026-10-14', '2026-10-15', '2026-10-16']);
        $this->orderWithBookedDays('4500000003', ['2026-10-12', '2026-10-13', '2026-10-14', '2026-10-15']);
        $this->orderWithBookedDays('4500000005', []);
    }

    public static function allowedRoles(): array
    {
        return [
            'project lead' => [Role::ProjectLead],
            'purchasing' => [Role::Purchasing],
        ];
    }

    #[DataProvider('allowedRoles')]
    public function test_it_lists_only_orders_that_need_a_follow_up(Role $role): void
    {
        $this->threeOrders();

        $this->actingAs(User::factory()->create(['role' => $role]))
            ->get('/follow-ups')
            ->assertOk()
            ->assertSee('4500000009')
            ->assertSee('4500000003')
            ->assertDontSee('4500000005');
    }

    public function test_the_most_urgent_order_comes_first(): void
    {
        $this->threeOrders();

        // 4500000009 has the higher number but the earlier reorder date
        $this->actingAs(User::factory()->create(['role' => Role::Purchasing]))
            ->get('/follow-ups')
            ->assertSeeInOrder(['4500000009', 'Overdue by 3 days', '4500000003', 'In 11 days']);
    }

    public function test_it_says_so_when_nothing_is_due(): void
    {
        $this->orderWithBookedDays('4500000005', []);

        $this->actingAs(User::factory()->create(['role' => Role::Purchasing]))
            ->get('/follow-ups')
            ->assertOk()
            ->assertSee('No order needs a follow-up order');
    }

    public function test_finance_and_consultants_get_a_403(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Finance]))
            ->get('/follow-ups')
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => Role::Consultant]))
            ->get('/follow-ups')
            ->assertForbidden();
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get('/follow-ups')->assertRedirect('/login');
    }
}
