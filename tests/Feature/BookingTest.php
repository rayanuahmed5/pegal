<?php

namespace Tests\Feature;

use App\Domain\BookingStatus;
use App\Domain\Role;
use App\Models\Booking;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    // a consultant who is assigned to one order, that's all most tests here need
    private function consultantWithOrder(): array
    {
        $supplier = Supplier::create(['name' => 'Test Consulting']);

        $consultant = User::factory()->create([
            'role' => Role::Consultant,
            'supplier_id' => $supplier->id,
        ]);

        $order = Order::create([
            'number' => '4500000001',
            'title' => 'Test order',
            'supplier_id' => $supplier->id,
            'cost_center' => 'CC-1',
            'budget_pd' => 10,
            'starts_on' => '2026-11-01',
            'ends_on' => '2026-12-31',
            'reorder_lead_weeks' => 4,
        ]);
        $order->consultants()->attach($consultant);

        return [$consultant, $order];
    }

    private function validBooking(Order $order, array $changes = []): array
    {
        return array_merge([
            'order_id' => $order->id,
            'worked_on' => '2026-11-02',
            'hours' => '7.5',
            'description' => 'Reconciling legacy data',
        ], $changes);
    }

    public function test_a_consultant_can_book_time_on_an_assigned_order(): void
    {
        [$consultant, $order] = $this->consultantWithOrder();

        $this->actingAs($consultant)
            ->post('/bookings', $this->validBooking($order))
            ->assertRedirect('/bookings');

        $this->assertDatabaseHas('bookings', [
            'user_id' => $consultant->id,
            'order_id' => $order->id,
            'hours' => 7.5,
            'status' => BookingStatus::Submitted->value,
        ]);
    }

    public function test_a_booking_on_an_order_the_consultant_is_not_assigned_to_is_rejected(): void
    {
        [$consultant] = $this->consultantWithOrder();

        $otherOrder = Order::create([
            'number' => '4500000002',
            'title' => 'Somebody else\'s order',
            'supplier_id' => $consultant->supplier_id,
            'cost_center' => 'CC-2',
            'budget_pd' => 10,
            'starts_on' => '2026-11-01',
            'ends_on' => '2026-12-31',
            'reorder_lead_weeks' => 4,
        ]);

        $this->actingAs($consultant)
            ->post('/bookings', $this->validBooking($otherOrder))
            ->assertSessionHasErrors('order_id');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_hours_must_be_in_quarter_hour_steps(): void
    {
        [$consultant, $order] = $this->consultantWithOrder();

        $this->actingAs($consultant)
            ->post('/bookings', $this->validBooking($order, ['hours' => '7.3']))
            ->assertSessionHasErrors('hours');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_description_is_required(): void
    {
        [$consultant, $order] = $this->consultantWithOrder();

        $this->actingAs($consultant)
            ->post('/bookings', $this->validBooking($order, ['description' => '']))
            ->assertSessionHasErrors('description');
    }

    public function test_a_consultant_only_sees_their_own_bookings(): void
    {
        [$consultant, $order] = $this->consultantWithOrder();

        $colleague = User::factory()->create(['role' => Role::Consultant, 'supplier_id' => $consultant->supplier_id]);
        $order->consultants()->attach($colleague);

        Booking::create([
            'order_id' => $order->id,
            'user_id' => $consultant->id,
            'worked_on' => '2026-11-02',
            'hours' => 8,
            'description' => 'My own work',
            'status' => BookingStatus::Submitted,
        ]);
        Booking::create([
            'order_id' => $order->id,
            'user_id' => $colleague->id,
            'worked_on' => '2026-11-02',
            'hours' => 8,
            'description' => 'Colleague work',
            'status' => BookingStatus::Submitted,
        ]);

        $this->actingAs($consultant)->get('/bookings')
            ->assertOk()
            ->assertSee('My own work')
            ->assertDontSee('Colleague work');
    }

    public function test_the_client_roles_cannot_open_the_booking_pages(): void
    {
        [, $order] = $this->consultantWithOrder();

        foreach ([Role::ProjectLead, Role::Purchasing, Role::Finance] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/bookings')->assertForbidden();
            $this->actingAs($user)->post('/bookings', $this->validBooking($order))->assertForbidden();
        }

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get('/bookings')->assertRedirect('/login');
    }
}
