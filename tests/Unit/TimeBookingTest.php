<?php

namespace Tests\Unit;

use App\Domain\BookingRejected;
use App\Domain\BookingStatus;
use App\Domain\Role;
use App\Domain\TimeBooking;
use App\Models\Booking;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// the booking rules, one accepting and one rejecting case each, plus the edges
class TimeBookingTest extends TestCase
{
    use RefreshDatabase;

    private function consultant(): User
    {
        $supplier = Supplier::firstOrCreate(['name' => 'Test Consulting']);

        return User::factory()->create([
            'role' => Role::Consultant,
            'supplier_id' => $supplier->id,
        ]);
    }

    // an order the given consultant is assigned to
    private function order(User $consultant, float $budgetPd = 10, string $from = '2026-11-01', string $to = '2026-12-31', string $number = '4500000001'): Order
    {
        $order = Order::create([
            'number' => $number,
            'title' => 'Test order',
            'supplier_id' => $consultant->supplier_id,
            'cost_center' => 'CC-1',
            'budget_pd' => $budgetPd,
            'starts_on' => $from,
            'ends_on' => $to,
            'reorder_lead_weeks' => 4,
        ]);
        $order->consultants()->attach($consultant);

        return $order;
    }

    private function book(User $consultant, Order $order, string $day = '2026-11-02', float $hours = 8): Booking
    {
        return app(TimeBooking::class)->book($consultant, $order, $day, $hours, 'Some work');
    }

    // puts hours on the books without going through the rules, just to set the scene
    private function alreadyBooked(User $consultant, Order $order, string $day, float $hours, BookingStatus $status = BookingStatus::Approved): void
    {
        Booking::create([
            'order_id' => $order->id,
            'user_id' => $consultant->id,
            'worked_on' => $day,
            'hours' => $hours,
            'description' => 'Earlier work',
            'status' => $status,
        ]);
    }

    public function test_it_books_time_when_every_rule_is_met(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);

        $booking = $this->book($consultant, $order, '2026-11-02', 7.5);

        $this->assertSame(BookingStatus::Submitted, $booking->status);
        $this->assertDatabaseCount('bookings', 1);
    }

    // budget

    public function test_it_accepts_exactly_the_remaining_budget(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant, budgetPd: 1);

        $this->book($consultant, $order, '2026-11-02', 8);

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_it_rejects_hours_beyond_the_remaining_budget(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant, budgetPd: 1);
        $this->alreadyBooked($consultant, $order, '2026-11-03', 6);

        $this->expectException(BookingRejected::class);
        $this->expectExceptionMessage('only 0.25 PD left on order 4500000001');

        $this->book($consultant, $order, '2026-11-02', 3);
    }

    public function test_rejected_bookings_do_not_use_up_budget(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant, budgetPd: 1);
        $this->alreadyBooked($consultant, $order, '2026-11-03', 8, BookingStatus::Rejected);

        $this->book($consultant, $order, '2026-11-02', 8);

        $this->assertDatabaseCount('bookings', 2);
    }

    // hours per day

    public function test_it_accepts_ten_hours_in_one_day(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);
        $this->alreadyBooked($consultant, $order, '2026-11-02', 6);

        $this->book($consultant, $order, '2026-11-02', 4);

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_it_rejects_more_than_ten_hours_in_one_day(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);
        $this->alreadyBooked($consultant, $order, '2026-11-02', 8);

        $this->expectException(BookingRejected::class);
        $this->expectExceptionMessage('that would be 10.25 hours on 2 Nov 2026');

        $this->book($consultant, $order, '2026-11-02', 2.25);
    }

    public function test_the_daily_limit_counts_all_orders_of_the_person(): void
    {
        $consultant = $this->consultant();
        $orderA = $this->order($consultant, number: '4500000001');
        $orderB = $this->order($consultant, number: '4500000002');
        $this->alreadyBooked($consultant, $orderA, '2026-11-02', 8);

        $this->expectException(BookingRejected::class);
        $this->expectExceptionMessage('the limit is 10 hours per day');

        $this->book($consultant, $orderB, '2026-11-02', 3);
    }

    public function test_the_daily_limit_is_per_person(): void
    {
        $consultant = $this->consultant();
        $colleague = $this->consultant();
        $order = $this->order($consultant);
        $this->alreadyBooked($colleague, $order, '2026-11-02', 9);

        $this->book($consultant, $order, '2026-11-02', 8);

        $this->assertDatabaseCount('bookings', 2);
    }

    // order period

    public function test_it_accepts_the_first_and_the_last_day_of_the_order(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant, from: '2026-11-02', to: '2026-11-04');

        $this->book($consultant, $order, '2026-11-02', 4);
        $this->book($consultant, $order, '2026-11-04', 4);

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_it_rejects_a_day_before_the_order_starts(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant, from: '2026-11-02', to: '2026-11-04');

        $this->expectException(BookingRejected::class);
        $this->expectExceptionMessage('31 Oct 2026 is outside the period of order 4500000001');

        $this->book($consultant, $order, '2026-10-31', 4);
    }

    public function test_it_rejects_a_day_after_the_order_ends(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant, from: '2026-11-02', to: '2026-11-04');

        $this->expectException(BookingRejected::class);
        $this->expectExceptionMessage('5 Nov 2026 is outside the period of order 4500000001');

        $this->book($consultant, $order, '2026-11-05', 4);
    }

    // assignment

    public function test_it_rejects_a_consultant_who_is_not_assigned_to_the_order(): void
    {
        $assigned = $this->consultant();
        $outsider = $this->consultant();
        $order = $this->order($assigned);

        $this->expectException(BookingRejected::class);
        $this->expectExceptionMessage('you are not assigned to order 4500000001');

        $this->book($outsider, $order);
    }

    public function test_a_rejected_booking_is_not_saved(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant, budgetPd: 1);

        try {
            $this->book($consultant, $order, '2026-11-02', 9);
        } catch (BookingRejected) {
            // expected, what matters is below
        }

        $this->assertDatabaseCount('bookings', 0);
    }
}
