<?php

namespace Tests\Unit;

use App\Domain\FollowUp;
use App\Domain\ForecastResult;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

// today is Monday 2 Nov 2026, six weeks later is Monday 14 Dec
class FollowUpTest extends TestCase
{
    private function forecastWithReorderDate(?string $reorderDate): ForecastResult
    {
        return new ForecastResult(
            burnPerWeek: 5,
            exhaustionDate: $reorderDate ? CarbonImmutable::parse($reorderDate)->addWeeks(4) : null,
            reorderDate: $reorderDate ? CarbonImmutable::parse($reorderDate) : null,
            light: 'amber',
            assumptions: [],
        );
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-11-02');
    }

    public function test_an_order_that_lasts_until_its_end_needs_no_follow_up(): void
    {
        $this->assertNull(FollowUp::status($this->forecastWithReorderDate(null), $this->today()));
    }

    public function test_a_reorder_date_in_the_past_is_overdue(): void
    {
        $this->assertSame('overdue', FollowUp::status($this->forecastWithReorderDate('2026-11-01'), $this->today()));
    }

    public function test_a_reorder_date_of_today_is_due_soon_not_overdue(): void
    {
        $this->assertSame('soon', FollowUp::status($this->forecastWithReorderDate('2026-11-02'), $this->today()));
    }

    public function test_exactly_six_weeks_ahead_is_still_due(): void
    {
        $this->assertSame('soon', FollowUp::status($this->forecastWithReorderDate('2026-12-14'), $this->today()));
    }

    public function test_one_day_beyond_six_weeks_is_not_due_yet(): void
    {
        $this->assertNull(FollowUp::status($this->forecastWithReorderDate('2026-12-15'), $this->today()));
    }

    public function test_it_counts_the_days_until_the_reorder_date(): void
    {
        $this->assertSame(-3, FollowUp::daysUntilReorder($this->forecastWithReorderDate('2026-10-30'), $this->today()));
        $this->assertSame(0, FollowUp::daysUntilReorder($this->forecastWithReorderDate('2026-11-02'), $this->today()));
        $this->assertSame(11, FollowUp::daysUntilReorder($this->forecastWithReorderDate('2026-11-13'), $this->today()));
        $this->assertNull(FollowUp::daysUntilReorder($this->forecastWithReorderDate(null), $this->today()));
    }
}
