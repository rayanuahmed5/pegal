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

    // usedPd is what was booked inside that window (rejected bookings already left out)
    public static function perWeek(float $usedPdInWindow): float
    {
        return $usedPdInWindow / self::WINDOW_WEEKS;
    }
}
