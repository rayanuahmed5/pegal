<?php

namespace App\Domain;

use Carbon\CarbonImmutable;

// how fast an order eats its budget: person-days per calendar week over the last 4 weeks
// (the definition from docs/domain.md)
final class BurnRate
{
    public const WINDOW_WEEKS = 4;

    // the 28 days before today, today itself is still in the making and left out
    public static function windowStart(CarbonImmutable $today): CarbonImmutable
    {
        return $today->subWeeks(self::WINDOW_WEEKS);
    }

    public static function windowEnd(CarbonImmutable $today): CarbonImmutable
    {
        return $today->subDay();
    }

    // The slowest and the fastest of the four weeks. The average hides how much the pace
    // jumps around, so the forecast also shows what happens if the quiet or the busy week repeats.
    // Weeks without bookings are not in the list, they count as 0.
    //
    // @param  array<int, float>  $weeklyPd  person-days of the weeks that had bookings
    // @return array{0: float, 1: float}  slowest, fastest
    public static function slowestAndFastest(array $weeklyPd): array
    {
        $weeks = array_pad(array_map('floatval', array_values($weeklyPd)), self::WINDOW_WEEKS, 0.0);

        return [min($weeks), max($weeks)];
    }

    // usedPd is what was booked inside that window (rejected bookings already left out)
    public static function perWeek(float $usedPdInWindow): float
    {
        return $usedPdInWindow / self::WINDOW_WEEKS;
    }
}
