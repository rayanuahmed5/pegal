<?php

namespace App\Domain;

use Carbon\CarbonImmutable;

// what the forecast says about one order, plus the assumptions behind it
// the assumptions are shown next to the date, a forecast nobody can check is just a guess
final class ForecastResult
{
    /**
     * @param  string  $light  green, amber or red
     * @param  array<int, string>  $assumptions  short sentences for the page
     * @param  float  $missingPd  person-days still needed after the budget ends, until the order ends
     * @param  ?CarbonImmutable  $earliestExhaustionDate  if the fastest of the last 4 weeks repeats
     * @param  ?CarbonImmutable  $latestExhaustionDate  if the slowest of the last 4 weeks repeats (null: lasts until the end)
     */
    public function __construct(
        public readonly float $burnPerWeek,
        public readonly ?CarbonImmutable $exhaustionDate,
        public readonly ?CarbonImmutable $reorderDate,
        public readonly string $light,
        public readonly array $assumptions,
        public readonly float $missingPd = 0.0,
        public readonly ?CarbonImmutable $earliestExhaustionDate = null,
        public readonly ?CarbonImmutable $latestExhaustionDate = null,
    ) {
    }

    // true when the fast and the slow case give different dates, so the range is worth showing
    public function hasRange(): bool
    {
        return $this->earliestExhaustionDate?->toDateString() !== $this->latestExhaustionDate?->toDateString();
    }

    public function earliestWeek(): ?int
    {
        return $this->earliestExhaustionDate === null ? null : (int) $this->earliestExhaustionDate->format('W');
    }

    public function latestWeek(): ?int
    {
        return $this->latestExhaustionDate === null ? null : (int) $this->latestExhaustionDate->format('W');
    }

    // ISO calendar week of the exhaustion date, planners at the client talk in weeks
    public function exhaustionWeek(): ?int
    {
        return $this->exhaustionDate === null ? null : (int) $this->exhaustionDate->format('W');
    }

    // ISO calendar week of the latest day to place the follow-up order
    public function reorderWeek(): ?int
    {
        return $this->reorderDate === null ? null : (int) $this->reorderDate->format('W');
    }

    // true when the budget is enough until the order ends (or nothing is being booked)
    public function lastsUntilEnd(): bool
    {
        return $this->exhaustionDate === null;
    }
}
