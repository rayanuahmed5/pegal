<?php

namespace Tests\Unit;

use App\Domain\BookingStatus;
use App\Domain\NothingToDecide;
use App\Domain\ProofOfWork;
use App\Domain\Role;
use App\Domain\TimeBooking;
use App\Models\Booking;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

// monthly sums per person and order, and approving or rejecting them
class ProofOfWorkTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    private User $ama;

    private User $jonas;

    protected function setUp(): void
    {
        parent::setUp();

        $supplier = Supplier::create(['name' => 'Test Consulting']);
        $this->ama = User::factory()->create(['name' => 'Ama', 'role' => Role::Consultant, 'supplier_id' => $supplier->id]);
        $this->jonas = User::factory()->create(['name' => 'Jonas', 'role' => Role::Consultant, 'supplier_id' => $supplier->id]);
        $this->order = Order::create([
            'number' => '4500000001',
            'title' => 'Test order',
            'supplier_id' => $supplier->id,
            'cost_center' => 'CC-1',
            'budget_pd' => 1,
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-12-31',
            'reorder_lead_weeks' => 4,
        ]);
        $this->order->consultants()->attach([$this->ama->id, $this->jonas->id]);
    }

    private function book(User $user, string $day, float $hours, BookingStatus $status = BookingStatus::Submitted): void
    {
        Booking::create([
            'order_id' => $this->order->id,
            'user_id' => $user->id,
            'worked_on' => $day,
            'hours' => $hours,
            'description' => 'Work',
            'status' => $status,
        ]);
    }

    private function october(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-10-15');
    }

    private function status(User $user, string $day): BookingStatus
    {
        return Booking::where('user_id', $user->id)->where('worked_on', $day)->firstOrFail()->status;
    }

    public function test_it_sums_the_hours_per_person_and_order_for_one_month(): void
    {
        $this->book($this->ama, '2026-10-01', 8);
        $this->book($this->ama, '2026-10-02', 4);
        $this->book($this->jonas, '2026-10-01', 6);
        $this->book($this->ama, '2026-09-30', 8); // September, not part of October
        $this->book($this->ama, '2026-11-02', 8); // November

        $rows = (new ProofOfWork())->monthlyRows($this->october());

        $this->assertCount(2, $rows);
        $this->assertSame('Ama', $rows[0]['consultant']->name);
        $this->assertSame(12.0, $rows[0]['submitted']);
        $this->assertSame('Jonas', $rows[1]['consultant']->name);
        $this->assertSame(6.0, $rows[1]['submitted']);
    }

    public function test_it_splits_the_hours_by_status(): void
    {
        $this->book($this->ama, '2026-10-01', 8, BookingStatus::Approved);
        $this->book($this->ama, '2026-10-02', 4, BookingStatus::Submitted);
        $this->book($this->ama, '2026-10-05', 2, BookingStatus::Rejected);

        $row = (new ProofOfWork())->monthlyRows($this->october())->first();

        $this->assertSame(4.0, $row['submitted']);
        $this->assertSame(8.0, $row['approved']);
        $this->assertSame(2.0, $row['rejected']);
    }

    public function test_a_month_without_bookings_has_no_rows(): void
    {
        $this->assertCount(0, (new ProofOfWork())->monthlyRows($this->october()));
    }

    public function test_approving_decides_only_that_person_that_order_and_that_month(): void
    {
        $this->book($this->ama, '2026-10-01', 4);
        $this->book($this->ama, '2026-10-02', 4);
        $this->book($this->jonas, '2026-10-01', 4);
        $this->book($this->ama, '2026-11-02', 4);

        $decided = (new ProofOfWork())->decide($this->order, $this->ama, $this->october(), BookingStatus::Approved);

        $this->assertSame(2, $decided);
        $this->assertSame(BookingStatus::Approved, $this->status($this->ama, '2026-10-01'));
        $this->assertSame(BookingStatus::Approved, $this->status($this->ama, '2026-10-02'));
        $this->assertSame(BookingStatus::Submitted, $this->status($this->jonas, '2026-10-01'));
        $this->assertSame(BookingStatus::Submitted, $this->status($this->ama, '2026-11-02'));
    }

    public function test_rejecting_marks_the_hours_as_rejected(): void
    {
        $this->book($this->ama, '2026-10-01', 4);

        (new ProofOfWork())->decide($this->order, $this->ama, $this->october(), BookingStatus::Rejected);

        $this->assertSame(BookingStatus::Rejected, $this->status($this->ama, '2026-10-01'));
    }

    public function test_rejected_hours_give_their_budget_back(): void
    {
        // the order has 1 PD, a full day is booked and waiting
        $this->book($this->ama, '2026-10-01', 8);

        $this->expectNotToPerformAssertions();

        (new ProofOfWork())->decide($this->order, $this->ama, $this->october(), BookingStatus::Rejected);

        // the 8 hours are free again, so this does not throw BookingRejected
        app(TimeBooking::class)->book($this->jonas, $this->order, '2026-10-02', 8, 'Work');
    }

    public function test_hours_that_were_decided_before_are_not_changed(): void
    {
        $this->book($this->ama, '2026-10-01', 4, BookingStatus::Approved);
        $this->book($this->ama, '2026-10-02', 4, BookingStatus::Submitted);

        (new ProofOfWork())->decide($this->order, $this->ama, $this->october(), BookingStatus::Rejected);

        $this->assertSame(BookingStatus::Approved, $this->status($this->ama, '2026-10-01'));
        $this->assertSame(BookingStatus::Rejected, $this->status($this->ama, '2026-10-02'));
    }

    public function test_deciding_with_nothing_submitted_is_refused_with_a_message(): void
    {
        $this->book($this->ama, '2026-10-01', 4, BookingStatus::Approved);

        $this->expectException(NothingToDecide::class);
        $this->expectExceptionMessage('Nothing to decide: no submitted hours of Ama on order 4500000001 in October 2026.');

        (new ProofOfWork())->decide($this->order, $this->ama, $this->october(), BookingStatus::Approved);
    }

    public function test_a_decision_must_be_approved_or_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ProofOfWork())->decide($this->order, $this->ama, $this->october(), BookingStatus::Submitted);
    }
}
