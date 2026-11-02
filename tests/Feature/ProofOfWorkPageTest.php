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

// who may open the proof of work page and what approving or rejecting does
class ProofOfWorkPageTest extends TestCase
{
    use RefreshDatabase;

    private function finance(): User
    {
        return User::factory()->create(['role' => Role::Finance]);
    }

    // Ama booked 2 days in October on one order, both waiting for a decision
    private function submittedOctober(): array
    {
        $supplier = Supplier::create(['name' => 'Test Consulting']);
        $ama = User::factory()->create(['name' => 'Ama Mensah', 'role' => Role::Consultant, 'supplier_id' => $supplier->id]);
        $order = Order::create([
            'number' => '4500000001',
            'title' => 'Test order',
            'supplier_id' => $supplier->id,
            'cost_center' => 'CC-1',
            'budget_pd' => 10,
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-12-31',
            'reorder_lead_weeks' => 4,
        ]);
        $order->consultants()->attach($ama);

        foreach (['2026-10-05', '2026-10-06'] as $day) {
            Booking::create([
                'order_id' => $order->id,
                'user_id' => $ama->id,
                'worked_on' => $day,
                'hours' => 8,
                'description' => 'Secret details of the work',
                'status' => BookingStatus::Submitted,
            ]);
        }

        return [$order, $ama];
    }

    private function decision(Order $order, User $ama, string $decision, string $month = '2026-10'): array
    {
        return [
            'order_id' => $order->id,
            'user_id' => $ama->id,
            'month' => $month,
            'decision' => $decision,
            'reason' => $decision === 'reject' ? 'Hours do not match the invoice' : null,
        ];
    }

    public function test_finance_sees_the_monthly_sum_per_person(): void
    {
        $this->submittedOctober();

        $this->actingAs($this->finance())
            ->get('/proof-of-work?month=2026-10')
            ->assertOk()
            ->assertSee('October 2026')
            ->assertSee('Ama Mensah')
            ->assertSee('4500000001')
            ->assertSee('16'); // 2 days x 8 hours, still to check
    }

    public function test_the_page_shows_no_single_days_and_no_descriptions(): void
    {
        $this->submittedOctober();

        $this->actingAs($this->finance())
            ->get('/proof-of-work?month=2026-10')
            ->assertDontSee('Secret details of the work')
            ->assertDontSee('2026-10-05')
            ->assertDontSee('5 Oct');
    }

    public static function otherRoles(): array
    {
        return [
            'consultant' => [Role::Consultant],
            'project lead' => [Role::ProjectLead],
            'purchasing' => [Role::Purchasing],
        ];
    }

    #[DataProvider('otherRoles')]
    public function test_everybody_but_finance_gets_a_403_on_the_page(Role $role): void
    {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->get('/proof-of-work')
            ->assertForbidden();
    }

    #[DataProvider('otherRoles')]
    public function test_everybody_but_finance_gets_a_403_when_deciding(Role $role): void
    {
        [$order, $ama] = $this->submittedOctober();

        $this->actingAs(User::factory()->create(['role' => $role]))
            ->post('/proof-of-work', $this->decision($order, $ama, 'approve'))
            ->assertForbidden();

        $this->assertDatabaseMissing('bookings', ['status' => BookingStatus::Approved->value]);
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get('/proof-of-work')->assertRedirect('/login');
    }

    public function test_finance_can_approve_the_month(): void
    {
        [$order, $ama] = $this->submittedOctober();

        $this->actingAs($this->finance())
            ->post('/proof-of-work', $this->decision($order, $ama, 'approve'))
            ->assertRedirect('/proof-of-work?month=2026-10')
            ->assertSessionHas('status');

        $this->assertSame(2, Booking::where('status', BookingStatus::Approved->value)->count());
    }

    public function test_finance_can_reject_the_month(): void
    {
        [$order, $ama] = $this->submittedOctober();

        $this->actingAs($this->finance())
            ->post('/proof-of-work', $this->decision($order, $ama, 'reject'));

        $this->assertSame(2, Booking::where('status', BookingStatus::Rejected->value)->count());
    }

    public function test_deciding_twice_shows_the_message_and_changes_nothing(): void
    {
        [$order, $ama] = $this->submittedOctober();
        $finance = $this->finance();

        $this->actingAs($finance)->post('/proof-of-work', $this->decision($order, $ama, 'approve'));

        $this->actingAs($finance)
            ->from('/proof-of-work?month=2026-10')
            ->post('/proof-of-work', $this->decision($order, $ama, 'reject'))
            ->assertRedirect('/proof-of-work?month=2026-10')
            ->assertSessionHasErrors('decision');

        $this->assertSame(2, Booking::where('status', BookingStatus::Approved->value)->count());
    }

    public function test_a_made_up_decision_is_refused(): void
    {
        [$order, $ama] = $this->submittedOctober();

        $this->actingAs($this->finance())
            ->post('/proof-of-work', $this->decision($order, $ama, 'delete'))
            ->assertSessionHasErrors('decision');
    }

    public function test_rejecting_without_a_reason_shows_a_message_and_changes_nothing(): void
    {
        [$order, $ama] = $this->submittedOctober();

        $this->actingAs($this->finance())
            ->from('/proof-of-work?month=2026-10')
            ->post('/proof-of-work', array_merge($this->decision($order, $ama, 'reject'), ['reason' => '']))
            ->assertSessionHasErrors('reason');

        $this->assertSame(2, Booking::where('status', BookingStatus::Submitted->value)->count());
    }

    public function test_the_consultant_sees_who_rejected_the_hours_and_why(): void
    {
        [$order, $ama] = $this->submittedOctober();
        $finance = User::factory()->create(['name' => 'Miriam Schulz', 'role' => Role::Finance]);

        $this->actingAs($finance)->post('/proof-of-work', $this->decision($order, $ama, 'reject'));

        $this->actingAs($ama)
            ->get('/bookings')
            ->assertOk()
            ->assertSee('Rejected by Miriam Schulz')
            ->assertSee('Reason: Hours do not match the invoice');
    }
}
