<?php

namespace Tests\Unit;

use App\Domain\BookingStatus;
use App\Domain\Role;
use App\Domain\UserOverview;
use App\Models\Booking;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// the numbers the administrator sees: users per role, assigned PD, booked PD
class UserOverviewTest extends TestCase
{
    use RefreshDatabase;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplier = Supplier::create(['name' => 'Test Consulting']);
    }

    private function consultant(string $name): User
    {
        return User::factory()->create(['name' => $name, 'role' => Role::Consultant, 'supplier_id' => $this->supplier->id]);
    }

    private function order(string $number, float $budgetPd, User ...$consultants): Order
    {
        $order = Order::create([
            'number' => $number,
            'title' => 'Order '.$number,
            'supplier_id' => $this->supplier->id,
            'cost_center' => 'CC-1',
            'budget_pd' => $budgetPd,
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-12-31',
            'reorder_lead_weeks' => 4,
        ]);
        $order->consultants()->attach(array_map(fn (User $user) => $user->id, $consultants));

        return $order;
    }

    private function book(User $user, Order $order, string $day, float $hours, BookingStatus $status = BookingStatus::Approved): void
    {
        Booking::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'worked_on' => $day,
            'hours' => $hours,
            'description' => 'Work',
            'status' => $status,
        ]);
    }

    private function rowOf(User $user): array
    {
        return (new UserOverview())->rows()->first(fn (array $row) => $row['user']->is($user));
    }

    public function test_it_counts_all_users_and_every_role(): void
    {
        $this->consultant('Ama');
        $this->consultant('Jonas');
        User::factory()->create(['role' => Role::Finance]);

        $overview = new UserOverview();

        $this->assertSame(3, $overview->total());
        $counts = $overview->countByRole();
        $this->assertSame(2, $counts['consultant']);
        $this->assertSame(1, $counts['finance']);
        $this->assertSame(0, $counts['admin']);
        $this->assertCount(count(Role::cases()), $counts);
    }

    public function test_it_adds_up_the_orders_a_person_is_assigned_to(): void
    {
        $ama = $this->consultant('Ama');
        $this->order('4500000001', 100, $ama);
        $this->order('4500000002', 40, $ama);

        $row = $this->rowOf($ama);

        $this->assertSame(2, $row['assignedOrders']);
        $this->assertSame(140.0, $row['assignedPd']);
    }

    public function test_it_turns_booked_hours_into_person_days(): void
    {
        $ama = $this->consultant('Ama');
        $order = $this->order('4500000001', 100, $ama);
        $this->book($ama, $order, '2026-10-01', 8);
        $this->book($ama, $order, '2026-10-02', 4);

        $this->assertSame(1.5, $this->rowOf($ama)['spentPd']);
    }

    public function test_rejected_hours_are_not_spent(): void
    {
        $ama = $this->consultant('Ama');
        $order = $this->order('4500000001', 100, $ama);
        $this->book($ama, $order, '2026-10-01', 8);
        $this->book($ama, $order, '2026-10-02', 8, BookingStatus::Rejected);

        $this->assertSame(1.0, $this->rowOf($ama)['spentPd']);
    }

    public function test_spent_is_per_person(): void
    {
        $ama = $this->consultant('Ama');
        $jonas = $this->consultant('Jonas');
        $order = $this->order('4500000001', 100, $ama, $jonas);
        $this->book($ama, $order, '2026-10-01', 8);

        $this->assertSame(1.0, $this->rowOf($ama)['spentPd']);
        $this->assertSame(0.0, $this->rowOf($jonas)['spentPd']);
    }

    public function test_a_user_without_orders_or_bookings_has_zeros(): void
    {
        $finance = User::factory()->create(['role' => Role::Finance]);

        $row = $this->rowOf($finance);

        $this->assertSame(0, $row['assignedOrders']);
        $this->assertSame(0.0, $row['assignedPd']);
        $this->assertSame(0.0, $row['spentPd']);
    }
}
