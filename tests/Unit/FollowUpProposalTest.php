<?php

namespace Tests\Unit;

use App\Domain\FollowUpProposal;
use App\Domain\ForecastResult;
use App\Domain\NoProposalNeeded;
use App\Domain\WorkCalendar;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

// the draft for the follow-up order: scope, start, end and the order-by date
class FollowUpProposalTest extends TestCase
{
    // the budget runs out on Friday 13 Nov 2026, 11.2 PD are missing until the order ends on 18 Dec
    private function forecast(?string $exhaustion = '2026-11-13', float $missingPd = 11.2): ForecastResult
    {
        return new ForecastResult(
            burnPerWeek: 4.0,
            exhaustionDate: $exhaustion ? CarbonImmutable::parse($exhaustion) : null,
            reorderDate: $exhaustion ? CarbonImmutable::parse($exhaustion)->subWeeks(4) : null,
            light: 'red',
            assumptions: [],
            missingPd: $missingPd,
        );
    }

    private function proposal(ForecastResult $forecast, ?WorkCalendar $calendar = null): FollowUpProposal
    {
        return FollowUpProposal::create($forecast, $calendar ?? new WorkCalendar(), CarbonImmutable::parse('2026-12-18'), 4);
    }

    public function test_the_missing_person_days_are_rounded_up(): void
    {
        $this->assertSame(12.0, $this->proposal($this->forecast())->suggestedPd);
        $this->assertSame(1.0, $this->proposal($this->forecast(missingPd: 0.1))->suggestedPd);
        $this->assertSame(11.0, $this->proposal($this->forecast(missingPd: 11.0))->suggestedPd);
    }

    public function test_the_new_order_starts_on_the_next_working_day(): void
    {
        // Friday 13 Nov, the weekend follows, so Monday 16 Nov
        $proposal = $this->proposal($this->forecast());

        $this->assertSame('2026-11-16', $proposal->startsOn->toDateString());
        $this->assertSame(47, $proposal->startWeek());
    }

    public function test_a_public_holiday_moves_the_start_further(): void
    {
        $proposal = $this->proposal($this->forecast(), new WorkCalendar(['2026-11-16']));

        $this->assertSame('2026-11-17', $proposal->startsOn->toDateString());
    }

    public function test_it_ends_with_the_current_order_and_must_be_ordered_by_the_reorder_date(): void
    {
        $proposal = $this->proposal($this->forecast());

        $this->assertSame('2026-12-18', $proposal->endsOn->toDateString());
        $this->assertSame(51, $proposal->endWeek());
        $this->assertSame('2026-10-16', $proposal->orderByDate->toDateString());
        $this->assertSame(42, $proposal->orderByWeek());
        $this->assertSame(4, $proposal->leadWeeks);
    }

    public function test_it_knows_when_the_order_by_date_has_passed(): void
    {
        $proposal = $this->proposal($this->forecast());

        $this->assertTrue($proposal->isOrderByOverdue(CarbonImmutable::parse('2026-10-17')));
        $this->assertFalse($proposal->isOrderByOverdue(CarbonImmutable::parse('2026-10-16')));
    }

    public function test_there_is_no_proposal_when_the_budget_lasts(): void
    {
        $this->expectException(NoProposalNeeded::class);
        $this->expectExceptionMessage('lasts until it ends');

        $this->proposal($this->forecast(exhaustion: null, missingPd: 0.0));
    }

    public function test_there_is_no_proposal_when_nothing_is_missing(): void
    {
        $this->expectException(NoProposalNeeded::class);
        $this->expectExceptionMessage('Nothing is missing');

        $this->proposal($this->forecast(missingPd: 0.0));
    }
}
