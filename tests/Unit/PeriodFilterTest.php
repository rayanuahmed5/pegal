<?php

namespace Tests\Unit;

use App\Domain\InvalidPeriod;
use App\Domain\PeriodFilter;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class PeriodFilterTest extends TestCase
{
    private function today(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-11-02');
    }

    public function test_no_input_means_no_filter(): void
    {
        $filter = PeriodFilter::fromInput(null, '', $this->today());

        $this->assertFalse($filter->isActive());
        $this->assertNull($filter->label());
    }

    public function test_a_year_covers_the_whole_year(): void
    {
        $filter = PeriodFilter::fromInput('2026', null, $this->today());

        $this->assertSame('2026-01-01', $filter->from);
        $this->assertSame('2026-12-31', $filter->to);
        $this->assertSame('2026', $filter->label());
    }

    public function test_a_month_and_year_cover_that_month(): void
    {
        $filter = PeriodFilter::fromInput('2026', '3', $this->today());

        $this->assertSame('2026-03-01', $filter->from);
        $this->assertSame('2026-03-31', $filter->to);
        $this->assertSame('March 2026', $filter->label());
    }

    public function test_a_month_alone_means_that_month_this_year(): void
    {
        $filter = PeriodFilter::fromInput('', '10', $this->today());

        $this->assertSame('2026-10-01', $filter->from);
        $this->assertSame('2026-10-31', $filter->to);
    }

    public function test_february_has_29_days_in_a_leap_year(): void
    {
        $this->assertSame('2028-02-29', PeriodFilter::fromInput('2028', '2', $this->today())->to);
        $this->assertSame('2026-02-28', PeriodFilter::fromInput('2026', '2', $this->today())->to);
    }

    public function test_december_ends_on_the_31st(): void
    {
        $this->assertSame('2026-12-31', PeriodFilter::fromInput('2026', '12', $this->today())->to);
    }

    public function test_a_month_beyond_twelve_is_refused_with_a_message(): void
    {
        $this->expectException(InvalidPeriod::class);
        $this->expectExceptionMessage('between January and December');

        PeriodFilter::fromInput('2026', '13', $this->today());
    }

    public function test_month_zero_is_refused(): void
    {
        $this->expectException(InvalidPeriod::class);

        PeriodFilter::fromInput('2026', '0', $this->today());
    }

    public function test_a_year_that_makes_no_sense_is_refused(): void
    {
        $this->expectException(InvalidPeriod::class);
        $this->expectExceptionMessage('between 2000 and 2100');

        PeriodFilter::fromInput('1850', null, $this->today());
    }

    public function test_text_instead_of_a_number_is_refused(): void
    {
        $this->expectException(InvalidPeriod::class);
        $this->expectExceptionMessage('not a valid month');

        PeriodFilter::fromInput('2026', 'march', $this->today());
    }
}
