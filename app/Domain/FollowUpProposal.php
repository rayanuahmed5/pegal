<?php

namespace App\Domain;

use Carbon\CarbonImmutable;

// the draft purchasing can take to the supplier: how many PD to order, from when to when,
// and by when the order has to go out. It only uses numbers the forecast already shows.
final class FollowUpProposal
{
    private function __construct(
        public readonly float $suggestedPd,
        public readonly CarbonImmutable $startsOn,
        public readonly CarbonImmutable $endsOn,
        public readonly CarbonImmutable $orderByDate,
        public readonly int $leadWeeks,
    ) {
    }

    /**
     * The new order starts on the first working day after the budget is gone and covers the
     * person-days that are missing until the current order's period ends, rounded up to whole days.
     *
     * @throws NoProposalNeeded when the budget lasts, or nothing is missing before the end
     */
    public static function create(ForecastResult $forecast, WorkCalendar $calendar, CarbonImmutable $orderEnd, int $leadWeeks): self
    {
        if ($forecast->exhaustionDate === null || $forecast->reorderDate === null) {
            throw new NoProposalNeeded('The budget of this order lasts until it ends, so no follow-up order is needed.');
        }

        $suggestedPd = ceil($forecast->missingPd);

        if ($suggestedPd <= 0) {
            throw new NoProposalNeeded('Nothing is missing before this order ends, so no follow-up order is needed.');
        }

        // weekends and public holidays can't be the first day of work
        $start = $forecast->exhaustionDate->addDay();
        while (! $calendar->isWorkingDay($start->toDateString())) {
            $start = $start->addDay();
        }

        return new self($suggestedPd, $start, $orderEnd, $forecast->reorderDate, $leadWeeks);
    }

    public function startWeek(): int
    {
        return (int) $this->startsOn->format('W');
    }

    public function endWeek(): int
    {
        return (int) $this->endsOn->format('W');
    }

    public function orderByWeek(): int
    {
        return (int) $this->orderByDate->format('W');
    }

    public function isOrderByOverdue(CarbonImmutable $today): bool
    {
        return $this->orderByDate->toDateString() < $today->toDateString();
    }
}
