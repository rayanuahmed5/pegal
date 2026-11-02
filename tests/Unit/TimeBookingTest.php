<?php

namespace Tests\Unit;

use App\Domain\BookingRejected;
use App\Domain\BookingStatus;
use App\Domain\Role;
use App\Domain\TimeBooking;
use App\Models\Absence;
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

    // planned absence

    public function test_it_rejects_a_day_inside_a_planned_absence(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);
        Absence::create(['user_id' => $consultant->id, 'starts_on' => '2026-11-16', 'ends_on' => '2026-11-20']);

        $this->expectException(BookingRejected::class);
        $this->expectExceptionMessage('you have a planned absence from 16 Nov 2026 to 20 Nov 2026, which includes 18 Nov 2026');

        $this->book($consultant, $order, '2026-11-18', 4);
    }

    public function test_the_first_and_last_day_of_an_absence_are_blocked_too(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);
        Absence::create(['user_id' => $consultant->id, 'starts_on' => '2026-11-16', 'ends_on' => '2026-11-20']);

        foreach (['2026-11-16', '2026-11-20'] as $day) {
            try {
                $this->book($consultant, $order, $day, 4);
                $this->fail("{$day} should have been rejected");
            } catch (BookingRejected) {
                // expected
            }
        }

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_the_days_around_an_absence_can_be_booked(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);
        Absence::create(['user_id' => $consultant->id, 'starts_on' => '2026-11-16', 'ends_on' => '2026-11-20']);

        $this->book($consultant, $order, '2026-11-13', 4);
        $this->book($consultant, $order, '2026-11-23', 4);

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_the_absence_of_a_colleague_does_not_block_booking(): void
    {
        $consultant = $this->consultant();
        $colleague = $this->consultant();
        $order = $this->order($consultant);
        Absence::create(['user_id' => $colleague->id, 'starts_on' => '2026-11-16', 'ends_on' => '2026-11-20']);

        $this->book($consultant, $order, '2026-11-18', 4);

        $this->assertDatabaseCount('bookings', 1);
    }

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

    // start and end times (ADR 0008)

    private function timed(User $consultant, Order $order, string $day, string $from, string $to, float $hours, BookingStatus $status = BookingStatus::Approved): void
    {
        Booking::create([
            'order_id' => $order->id,
            'user_id' => $consultant->id,
            'worked_on' => $day,
            'starts_at' => $from,
            'ends_at' => $to,
            'hours' => $hours,
            'description' => 'Earlier work',
            'status' => $status,
        ]);
    }

    private function bookTimed(User $consultant, Order $order, string $day, ?string $from, ?string $to, float $hours)
    {
        return app(TimeBooking::class)->book($consultant, $order, $day, $hours, 'Timed work', $from, $to);
    }

    public function test_it_keeps_start_and_end_when_they_fit_the_hours(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);

        $booking = $this->bookTimed($consultant, $order, '2026-11-02', '09:00', '17:00', 8);

        $this->assertSame('09:00:00', $booking->fresh()->starts_at);
        $this->assertSame('17:00:00', $booking->fresh()->ends_at);
    }

    public function test_it_rejects_an_end_before_the_start(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);

        $this->expectException(BookingRejected::class);
        $this->expectExceptionMessage('the end time must be after the start time');

        $this->bookTimed($consultant, $order, '2026-11-02', '17:00', '09:00', 8);
    }

    public function test_it_rejects_only_one_of_start_and_end(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);

        $this->expectException(BookingRejected::class);
        $this->expectExceptionMessage('please give both a start and an end time, or neither');

        $this->bookTimed($consultant, $order, '2026-11-02', '09:00', null, 8);
    }

    public function test_it_rejects_hours_that_do_not_match_start_and_end(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);

        $this->expectException(BookingRejected::class);
        $this->expectExceptionMessage('09:00 to 17:00 is 8 hours, but you entered 6 hours');

        $this->bookTimed($consultant, $order, '2026-11-02', '09:00', '17:00', 6);
    }

    public function test_it_rejects_times_that_are_not_quarter_hours(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);

        $this->expectException(BookingRejected::class);
        $this->expectExceptionMessage('please use quarter hours');

        $this->bookTimed($consultant, $order, '2026-11-02', '09:10', '12:00', 2.8333);
    }

    // overlap

    public function test_it_rejects_a_booking_that_overlaps_another_one_of_the_same_day(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);
        $this->timed($consultant, $order, '2026-11-02', '09:00', '13:00', 4);

        $this->expectException(BookingRejected::class);
        $this->expectExceptionMessage('12:00 to 16:00 overlaps your booking from 09:00 to 13:00 on 2 Nov 2026');

        $this->bookTimed($consultant, $order, '2026-11-02', '12:00', '16:00', 4);
    }

    public function test_back_to_back_bookings_do_not_overlap(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);
        $this->timed($consultant, $order, '2026-11-02', '09:00', '13:00', 4);

        $this->bookTimed($consultant, $order, '2026-11-02', '13:00', '17:00', 4);

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_a_rejected_booking_does_not_block_its_time_slot(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);
        $this->timed($consultant, $order, '2026-11-02', '09:00', '13:00', 4, BookingStatus::Rejected);

        $this->bookTimed($consultant, $order, '2026-11-02', '10:00', '12:00', 2);

        $this->assertDatabaseCount('bookings', 2);
    }

    // 11 hours of rest

    public function test_it_rejects_less_than_eleven_hours_of_rest_after_the_day_before(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);
        $this->timed($consultant, $order, '2026-11-02', '14:00', '22:00', 8);

        $this->expectException(BookingRejected::class);
        $this->expectExceptionMessage('only 10 hours of rest between work ending at 22:00 on 2 Nov 2026 and starting at 08:00 on 3 Nov 2026');

        $this->bookTimed($consultant, $order, '2026-11-03', '08:00', '12:00', 4);
    }

    public function test_exactly_eleven_hours_of_rest_is_fine(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);
        $this->timed($consultant, $order, '2026-11-02', '14:00', '22:00', 8);

        $this->bookTimed($consultant, $order, '2026-11-03', '09:00', '13:00', 4);

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_it_rejects_a_booking_that_leaves_too_little_rest_before_the_next_day(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);
        $this->timed($consultant, $order, '2026-11-03', '06:00', '10:00', 4);

        $this->expectException(BookingRejected::class);
        $this->expectExceptionMessage('only 10 hours of rest between work ending at 20:00 on 2 Nov 2026 and your booking starting at 06:00 on 3 Nov 2026');

        $this->bookTimed($consultant, $order, '2026-11-02', '12:00', '20:00', 8);
    }

    public function test_a_rejected_or_untimed_booking_the_day_before_does_not_count_for_the_rest(): void
    {
        $consultant = $this->consultant();
        $order = $this->order($consultant);
        $this->timed($consultant, $order, '2026-11-02', '14:00', '23:00', 9, BookingStatus::Rejected);
        $this->alreadyBooked($consultant, $order, '2026-11-02', 2);

        $this->bookTimed($consultant, $order, '2026-11-03', '07:00', '11:00', 4);

        $this->assertDatabaseCount('bookings', 3);
    }

    public function test_the_rest_rule_is_per_person(): void
    {
        $consultant = $this->consultant();
        $colleague = $this->consultant();
        $order = $this->order($consultant);
        $this->timed($colleague, $order, '2026-11-02', '14:00', '23:00', 9);

        $this->bookTimed($consultant, $order, '2026-11-03', '07:00', '11:00', 4);

        $this->assertDatabaseCount('bookings', 2);
    }
}
