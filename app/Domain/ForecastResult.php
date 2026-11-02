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
     */
    public function __construct(
        public readonly float $burnPerWeek,
        public readonly ?CarbonImmutable $exhaustionDate,
        public readonly ?CarbonImmutable $reorderDate,
        public readonly string $light,
        public readonly array $assumptions,
        public readonly float $missingPd = 0.0,
    ) {
    }

    // true when the budget is enough until the order ends (or nothing is being booked)
    public function lastsUntilEnd(): bool
    {
        return $this->exhaustionDate === null;
    }
}
