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

// who may open the budget page (role matrix) and what it shows
class OrderPageTest extends TestCase
{
    use RefreshDatabase;

    private function user(Role $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    // an order of 10 PD with one consultant who booked 4 PD, plus a rejected day that must not count
    private function orderWithBookings(): array
    {
        $supplier = Supplier::create(['name' => 'Test Consulting']);
        $consultant = User::factory()->create([
            'name' => 'Secret Consultant',
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

        foreach ([['2026-11-02', 8, BookingStatus::Approved], ['2026-11-03', 8, BookingStatus::Submitted], ['2026-11-04', 8, BookingStatus::Rejected]] as [$day, $hours, $status]) {
            Booking::create([
                'order_id' => $order->id,
                'user_id' => $consultant->id,
                'worked_on' => $day,
                'hours' => $hours,
                'description' => 'Work',
                'status' => $status,
            ]);
        }

        return [$order, $consultant];
    }

    public static function clientRoles(): array
    {
        return [
            'project lead' => [Role::ProjectLead],
            'purchasing' => [Role::Purchasing],
            'finance' => [Role::Finance],
        ];
    }

    #[DataProvider('clientRoles')]
    public function test_the_client_roles_can_open_the_orders_page(Role $role): void
    {
        $this->orderWithBookings();

        $this->actingAs($this->user($role))
            ->get('/orders')
            ->assertOk()
            ->assertSee('4500000001');
    }

    public function test_a_consultant_gets_a_403(): void
    {
        $this->actingAs($this->user(Role::Consultant))
            ->get('/orders')
            ->assertForbidden();
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get('/orders')->assertRedirect('/login');
    }

    public function test_rejected_bookings_do_not_count_as_used(): void
    {
        $this->orderWithBookings();

        // 2 booked days = 2 PD of 10, the rejected day is left out
        $this->actingAs($this->user(Role::ProjectLead))
            ->get('/orders')
            ->assertSee('20% used');
    }

    public function test_the_page_shows_no_person_names(): void
    {
        $this->orderWithBookings();

        $this->actingAs($this->user(Role::Purchasing))
            ->get('/orders')
            ->assertDontSee('Secret Consultant');
    }
}
