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

// the month and year filter on "My bookings"
class BookingFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $consultant;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $supplier = Supplier::create(['name' => 'Test Consulting']);
        $this->consultant = User::factory()->create(['role' => Role::Consultant, 'supplier_id' => $supplier->id]);

        $this->order = Order::create([
            'number' => '4500000001',
            'title' => 'Test order',
            'supplier_id' => $supplier->id,
            'cost_center' => 'CC-1',
            'budget_pd' => 100,
            'starts_on' => '2025-01-01',
            'ends_on' => '2026-12-31',
            'reorder_lead_weeks' => 4,
        ]);
        $this->order->consultants()->attach($this->consultant);
    }

    private function book(string $day, string $text, ?User $who = null, float $hours = 8): void
    {
        Booking::create([
            'order_id' => $this->order->id,
            'user_id' => ($who ?? $this->consultant)->id,
            'worked_on' => $day,
            'hours' => $hours,
            'description' => $text,
            'status' => BookingStatus::Submitted,
        ]);
    }

    public function test_without_a_filter_the_list_says_it_shows_the_latest_entries(): void
    {
        $this->book('2026-10-05', 'October job');

        $this->actingAs($this->consultant)->get('/bookings')
            ->assertOk()
            ->assertSee('The latest 50 entries')
            ->assertSee('October job');
    }

    public function test_a_month_and_year_show_only_that_month(): void
    {
        $this->book('2026-09-15', 'September job');
        $this->book('2026-10-15', 'October job');
        $this->book('2026-11-02', 'November job');

        $this->actingAs($this->consultant)->get('/bookings?year=2026&month=10')
            ->assertOk()
            ->assertSee('October job')
            ->assertDontSee('September job')
            ->assertDontSee('November job')
            ->assertSee('Everything you booked in October 2026');
    }

    public function test_the_first_and_last_day_of_the_month_are_included(): void
    {
        $this->book('2026-09-30', 'Day before');
        $this->book('2026-10-01', 'First day');
        $this->book('2026-10-31', 'Last day');
        $this->book('2026-11-01', 'Day after');

        $this->actingAs($this->consultant)->get('/bookings?year=2026&month=10')
            ->assertSee('First day')
            ->assertSee('Last day')
            ->assertDontSee('Day before')
            ->assertDontSee('Day after');
    }

    public function test_a_year_on_its_own_shows_the_whole_year(): void
    {
        $this->book('2025-12-31', 'Last year job');
        $this->book('2026-01-02', 'Winter job');
        $this->book('2026-12-15', 'Christmas job');

        $this->actingAs($this->consultant)->get('/bookings?year=2026')
            ->assertSee('Winter job')
            ->assertSee('Christmas job')
            ->assertDontSee('Last year job');
    }

    public function test_a_filtered_list_is_not_cut_off_at_fifty(): void
    {
        foreach (range(1, 28) as $day) {
            $this->book(sprintf('2026-10-%02d', $day), "Early job $day");
            $this->book(sprintf('2026-10-%02d', $day), "Late job $day", null, 1);
        }

        $this->actingAs($this->consultant)->get('/bookings?year=2026&month=10')
            ->assertSee('56 bookings');
    }

    public function test_the_summary_adds_up_the_hours(): void
    {
        $this->book('2026-10-05', 'Job one', null, 7.5);
        $this->book('2026-10-06', 'Job two', null, 4);

        $this->actingAs($this->consultant)->get('/bookings?year=2026&month=10')
            ->assertSee('2 bookings, 11.5 hours in October 2026');
    }

    public function test_an_empty_month_says_so(): void
    {
        $this->book('2026-10-05', 'October job');

        $this->actingAs($this->consultant)->get('/bookings?year=2026&month=3')
            ->assertOk()
            ->assertSee('Nothing booked in March 2026.')
            ->assertDontSee('October job');
    }

    public function test_a_made_up_month_shows_a_message_and_the_normal_list(): void
    {
        $this->book('2026-10-05', 'October job');

        $this->actingAs($this->consultant)->get('/bookings?year=2026&month=13')
            ->assertOk()
            ->assertSee('Please choose a month between January and December.')
            ->assertSee('October job');
    }

    public function test_text_in_the_address_is_refused_with_a_message(): void
    {
        $this->actingAs($this->consultant)->get('/bookings?year=abc')
            ->assertOk()
            ->assertSee('That is not a valid year.');
    }

    public function test_the_filter_never_shows_a_colleagues_bookings(): void
    {
        $colleague = User::factory()->create(['role' => Role::Consultant, 'supplier_id' => $this->consultant->supplier_id]);
        $this->order->consultants()->attach($colleague);

        $this->book('2026-10-05', 'My own work');
        $this->book('2026-10-05', 'Colleague work', $colleague);

        $this->actingAs($this->consultant)->get('/bookings?year=2026&month=10')
            ->assertSee('My own work')
            ->assertDontSee('Colleague work');
    }

    public function test_the_year_list_offers_the_years_with_bookings(): void
    {
        $this->book('2025-03-03', 'Old job');

        $this->actingAs($this->consultant)->get('/bookings')
            ->assertSee('<option value="2025">2025</option>', false);
    }

    public function test_clear_link_shows_only_while_a_filter_is_on(): void
    {
        $this->actingAs($this->consultant)->get('/bookings')->assertDontSee('>Clear<', false);
        $this->actingAs($this->consultant)->get('/bookings?year=2026')->assertSee('>Clear<', false);
    }
}
