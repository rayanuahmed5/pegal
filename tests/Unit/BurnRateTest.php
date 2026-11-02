<?php

namespace Tests\Unit;

use App\Domain\BurnRate;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class BurnRateTest extends TestCase
{
    public function test_it_is_the_average_per_week_over_four_weeks(): void
    {
        $this->assertSame(2.0, BurnRate::perWeek(8));
        $this->assertSame(4.5, BurnRate::perWeek(18));
    }

    public function test_an_order_nobody_booked_on_burns_nothing(): void
    {
        $this->assertSame(0.0, BurnRate::perWeek(0));
    }

    public function test_the_window_is_the_28_days_before_today(): void
    {
        $today = CarbonImmutable::parse('2026-11-02');

        $this->assertSame('2026-10-05', BurnRate::windowStart($today)->toDateString());
        $this->assertSame('2026-11-01', BurnRate::windowEnd($today)->toDateString());
    }
}
