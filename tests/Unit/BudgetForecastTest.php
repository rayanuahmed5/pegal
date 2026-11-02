<?php

namespace Tests\Unit;

use App\Domain\BudgetForecast;
use App\Domain\WorkCalendar;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

// today is Monday 2 Nov 2026 in all of these, the order ends on 5 Feb 2027
// at 5 PD per week and two consultants, 1 PD burns per working day
class BudgetForecastTest extends TestCase
{
    private function forecast(
        float $remaining,
        float $burnPerWeek,
        float $ordered = 100,
        ?WorkCalendar $calendar = null,
        string $end = '2027-02-05',
    ) {
        return (new BudgetForecast($calendar ?? new WorkCalendar()))->calculate(
            orderedPd: $ordered,
            remainingPd: $remaining,
            burnPerWeek: $burnPerWeek,
            today: CarbonImmutable::parse('2026-11-02'),
            orderEnd: CarbonImmutable::parse($end),
            reorderLeadWeeks: 4,
            consultantIds: [1, 2],
        );
    }

    public function test_it_finds_the_day_the_budget_runs_out(): void
    {
        // 10 PD at 1 PD per working day: Monday 2 Nov to Friday 13 Nov
        $result = $this->forecast(remaining: 10, burnPerWeek: 5, ordered: 20);

        $this->assertSame('2026-11-13', $result->exhaustionDate->toDateString());
    }

    public function test_a_public_holiday_pushes_the_date_back(): void
    {
        // Wednesday 4 Nov is a holiday, so the tenth working day is Monday 16 Nov
        $result = $this->forecast(10, 5, 20, new WorkCalendar(['2026-11-04']));

        $this->assertSame('2026-11-16', $result->exhaustionDate->toDateString());
    }

    public function test_a_vacation_pushes_the_date_back(): void
    {
        // one of two away in the first week: 2.5 PD that week, then 5 PD per week
        $calendar = new WorkCalendar([], [['user_id' => 2, 'from' => '2026-11-02', 'to' => '2026-11-06']]);

        $result = $this->forecast(10, 5, 20, $calendar);

        $this->assertSame('2026-11-18', $result->exhaustionDate->toDateString());
    }

    public function test_the_reorder_date_is_the_lead_time_before_the_exhaustion(): void
    {
        // 40 PD at 1 PD per day runs out on Friday 25 Dec, four weeks earlier is 27 Nov
        $result = $this->forecast(40, 5);

        $this->assertSame('2026-12-25', $result->exhaustionDate->toDateString());
        $this->assertSame('2026-11-27', $result->reorderDate->toDateString());
    }

    public function test_it_is_green_when_the_budget_lasts_until_the_order_ends(): void
    {
        // about 14 weeks left on the order, 70 PD burn, 100 PD in the pot
        $result = $this->forecast(100, 5);

        $this->assertNull($result->exhaustionDate);
        $this->assertNull($result->reorderDate);
        $this->assertTrue($result->lastsUntilEnd());
        $this->assertSame('green', $result->light);
    }

    public function test_it_is_amber_when_the_budget_ends_early_but_there_is_time_to_reorder(): void
    {
        $result = $this->forecast(40, 5);

        $this->assertSame('amber', $result->light);
    }

    public function test_it_is_red_when_the_reorder_date_has_passed(): void
    {
        // runs out on 13 Nov, the reorder date was 16 Oct
        $result = $this->forecast(10, 5, 20);

        $this->assertSame('red', $result->light);
    }

    public function test_a_reorder_date_of_today_is_still_amber(): void
    {
        // 21 PD runs out on Monday 30 Nov, four weeks earlier is today, 2 Nov: still time
        $result = $this->forecast(21, 5, 40);

        $this->assertSame('2026-11-02', $result->reorderDate->toDateString());
        $this->assertSame('amber', $result->light);
    }

    public function test_a_reorder_date_of_yesterday_is_red(): void
    {
        // 20 PD runs out on Friday 27 Nov, four weeks earlier is Friday 30 Oct: too late
        $result = $this->forecast(20, 5, 40);

        $this->assertSame('2026-10-30', $result->reorderDate->toDateString());
        $this->assertSame('red', $result->light);
    }

    public function test_it_is_red_when_less_than_ten_percent_is_left(): void
    {
        // plenty of time to reorder, but only 5 of 100 PD left
        $result = $this->forecast(5, 0.5);

        $this->assertSame('red', $result->light);
    }

    public function test_exactly_ten_percent_left_is_not_red_yet(): void
    {
        // 10 of 100 PD at 1 PD per week runs out in January, before the order ends
        $result = $this->forecast(10, 1);

        $this->assertSame('amber', $result->light);
    }

    public function test_a_used_up_order_runs_out_today_and_is_red(): void
    {
        $result = $this->forecast(0, 5);

        $this->assertSame('2026-11-02', $result->exhaustionDate->toDateString());
        $this->assertSame('red', $result->light);
    }

    public function test_an_order_without_bookings_never_runs_out(): void
    {
        $result = $this->forecast(100, 0);

        $this->assertNull($result->exhaustionDate);
        $this->assertSame('green', $result->light);
    }

    public function test_it_counts_the_person_days_missing_until_the_order_ends(): void
    {
        // runs out on Fri 13 Nov, from Mon 16 Nov to Thu 31 Dec are 34 working days at 1 PD each
        $result = $this->forecast(10, 5, 20, end: '2026-12-31');

        $this->assertSame(34.0, $result->missingPd);
    }

    public function test_a_holiday_in_the_gap_means_less_is_missing(): void
    {
        // Friday 25 Dec is a holiday: 33 working days instead of 34
        $result = $this->forecast(10, 5, 20, new WorkCalendar(['2026-12-25']), end: '2026-12-31');

        $this->assertSame(33.0, $result->missingPd);
    }

    public function test_nothing_is_missing_when_the_budget_lasts_until_the_end(): void
    {
        $result = $this->forecast(100, 5);

        $this->assertSame(0.0, $result->missingPd);
    }

    public function test_the_assumptions_are_part_of_the_result(): void
    {
        $result = $this->forecast(40, 5);

        $this->assertStringContainsString('5 PD per week', $result->assumptions[0]);
        $this->assertStringContainsString('4 weeks', $result->assumptions[3]);
    }
}
