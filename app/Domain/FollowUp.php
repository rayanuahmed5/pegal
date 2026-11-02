<?php

namespace App\Domain;

use Carbon\CarbonImmutable;

// does purchasing have to act on this order soon?
// an order is due when its latest reorder date is in the past or within the next 6 weeks
final class FollowUp
{
    public const WITHIN_WEEKS = 6;

    // 'overdue', 'soon' or null when there is nothing to do yet
    public static function status(ForecastResult $forecast, CarbonImmutable $today): ?string
    {
        if ($forecast->reorderDate === null) {
            return null;
        }

        // plain Y-m-d strings compare correctly
        $reorder = $forecast->reorderDate->toDateString();

        if ($reorder < $today->toDateString()) {
            return 'overdue';
        }

        return $reorder <= $today->addWeeks(self::WITHIN_WEEKS)->toDateString() ? 'soon' : null;
    }

    // negative when the date is already behind us, 0 means today
    public static function daysUntilReorder(ForecastResult $forecast, CarbonImmutable $today): ?int
    {
        if ($forecast->reorderDate === null) {
            return null;
        }

        return (int) round($today->startOfDay()->diffInDays($forecast->reorderDate->startOfDay(), false));
    }
}
