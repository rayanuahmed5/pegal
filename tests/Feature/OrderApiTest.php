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

// the JSON endpoints: same data and same permissions as the pages
// today is Mon 2 Nov 2026; order 4500000002 has 10 PD, 5 PD booked in the last 4 weeks,
// so it runs out on 27 Nov and its reorder date (30 Oct) has passed
class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-02');

        $supplier = Supplier::create(['name' => 'Test Consulting']);
        $consultant = User::factory()->create(['name' => 'Secret Consultant', 'role' => Role::Consultant, 'supplier_id' => $supplier->id]);
        $order = Order::create([
            'number' => '4500000002',
            'title' => 'API order',
            'supplier_id' => $supplier->id,
            'cost_center' => 'CC-2',
            'budget_pd' => 10,
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-12-31',
            'reorder_lead_weeks' => 4,
        ]);
        $order->consultants()->attach($consultant);

        foreach (['2026-10-12', '2026-10-13', '2026-10-14', '2026-10-15', '2026-10-16'] as $day) {
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

    private function user(Role $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public static function forecastRoles(): array
    {
        return ['project lead' => [Role::ProjectLead], 'purchasing' => [Role::Purchasing]];
    }

    #[DataProvider('forecastRoles')]
    public function test_project_lead_and_purchasing_get_budget_and_forecast(Role $role): void
    {
        $this->actingAs($this->user($role))
            ->getJson('/api/orders')
            ->assertOk()
            ->assertJsonPath('data.0.number', '4500000002')
            ->assertJsonPath('data.0.supplier', 'Test Consulting')
            ->assertJsonPath('data.0.budget.orderedPd', 10)
            ->assertJsonPath('data.0.budget.usedPd', 5)
            ->assertJsonPath('data.0.budget.remainingPd', 5)
            ->assertJsonPath('data.0.forecast.exhaustionDate', '2026-11-27')
            ->assertJsonPath('data.0.forecast.reorderDate', '2026-10-30')
            ->assertJsonPath('data.0.forecast.light', 'red')
            ->assertJsonStructure(['data' => [['forecast' => ['burnPerWeek', 'assumptions']]]]);
    }

    public function test_finance_gets_the_budget_but_no_forecast(): void
    {
        $this->actingAs($this->user(Role::Finance))
            ->getJson('/api/orders')
            ->assertOk()
            ->assertJsonPath('data.0.budget.usedPd', 5)
            ->assertJsonPath('data.0.forecast', null);
    }

    public function test_the_api_shows_no_person_names(): void
    {
        $response = $this->actingAs($this->user(Role::ProjectLead))->getJson('/api/orders');

        $this->assertStringNotContainsString('Secret Consultant', $response->getContent());
    }

    public function test_a_consultant_gets_a_403(): void
    {
        $this->actingAs($this->user(Role::Consultant))->getJson('/api/orders')->assertForbidden();
        $this->actingAs($this->user(Role::Consultant))->getJson('/api/follow-ups')->assertForbidden();
    }

    public function test_a_guest_gets_a_401(): void
    {
        $this->getJson('/api/orders')->assertUnauthorized();
        $this->getJson('/api/follow-ups')->assertUnauthorized();
    }

    #[DataProvider('forecastRoles')]
    public function test_the_follow_up_list_has_the_overdue_order(Role $role): void
    {
        $this->actingAs($this->user($role))
            ->getJson('/api/follow-ups')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.number', '4500000002')
            ->assertJsonPath('data.0.followUp.status', 'overdue')
            ->assertJsonPath('data.0.followUp.daysUntilReorder', -3);
    }

    public function test_finance_gets_a_403_on_the_follow_up_list(): void
    {
        $this->actingAs($this->user(Role::Finance))->getJson('/api/follow-ups')->assertForbidden();
    }
}
