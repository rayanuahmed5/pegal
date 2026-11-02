<?php

namespace App\Domain;

use Carbon\CarbonImmutable;

// when does the budget run out, and by when must purchasing order more?
// walks forward day by day from today, because holidays and vacations
// make some days burn less (see docs/domain.md)
final class BudgetForecast
{
    // an order is red when less than this share of the budget is left
    public const RED_BELOW_PERCENT_LEFT = 10;

    // a working week has five days, the weekly burn rate is spread over them
    private const WORKING_DAYS_PER_WEEK = 5;

    // float sums are never exact, this keeps 4.9999999 from counting as "not used up"
    private const EPSILON = 0.000001;

    public function __construct(private readonly WorkCalendar $calendar)
    {
    }

    /**
     * @param  array<int, int>  $consultantIds  the people assigned to the order
     */
    public function calculate(
        float $orderedPd,
        float $remainingPd,
        float $burnPerWeek,
        CarbonImmutable $today,
        CarbonImmutable $orderEnd,
        int $reorderLeadWeeks,
        array $consultantIds,
    ): ForecastResult {
        $exhaustion = $this->findExhaustionDate($remainingPd, $burnPerWeek, $today, $orderEnd, $consultantIds);
        $reorder = $exhaustion?->subWeeks($reorderLeadWeeks);

        return new ForecastResult(
            $burnPerWeek,
            $exhaustion,
            $reorder,
            $this->trafficLight($orderedPd, $remainingPd, $exhaustion, $reorder, $today),
            $this->assumptions($burnPerWeek, $reorderLeadWeeks),
            $exhaustion === null ? 0.0 : $this->missingPd($exhaustion, $burnPerWeek, $orderEnd, $consultantIds),
        );
    }

    // the first day the remaining budget reaches zero, or null if it lasts until the order ends
    private function findExhaustionDate(
        float $remainingPd,
        float $burnPerWeek,
        CarbonImmutable $today,
        CarbonImmutable $orderEnd,
        array $consultantIds,
    ): ?CarbonImmutable {
        if ($remainingPd <= self::EPSILON) {
            return $today;
        }

        if ($burnPerWeek <= 0) {
            return null;
        }

        $burnPerDay = $burnPerWeek / self::WORKING_DAYS_PER_WEEK;
        $left = $remainingPd;

        for ($day = $today; $day->toDateString() <= $orderEnd->toDateString(); $day = $day->addDay()) {
            $left -= $burnPerDay * $this->calendar->availableShare($day->toDateString(), $consultantIds);

            if ($left <= self::EPSILON) {
                return $day;
            }
        }

        return null;
    }

    // how many person-days the order would still burn between the day after the budget ends
    // and the order's end, at the same pace: the size of the follow-up order purchasing has to place
    private function missingPd(
        CarbonImmutable $exhaustion,
        float $burnPerWeek,
        CarbonImmutable $orderEnd,
        array $consultantIds,
    ): float {
        $burnPerDay = $burnPerWeek / self::WORKING_DAYS_PER_WEEK;
        $missing = 0.0;

        for ($day = $exhaustion->addDay(); $day->toDateString() <= $orderEnd->toDateString(); $day = $day->addDay()) {
            $missing += $burnPerDay * $this->calendar->availableShare($day->toDateString(), $consultantIds);
        }

        return round($missing, 1);
    }

    // green: lasts until the end. amber: runs out earlier but there is still time to reorder.
    // red: the reorder date has passed, or hardly any budget is left
    private function trafficLight(
        float $orderedPd,
        float $remainingPd,
        ?CarbonImmutable $exhaustion,
        ?CarbonImmutable $reorder,
        CarbonImmutable $today,
    ): string {
        $percentLeft = $orderedPd > 0 ? $remainingPd / $orderedPd * 100 : 0;

        if ($percentLeft < self::RED_BELOW_PERCENT_LEFT) {
            return 'red';
        }

        if ($exhaustion === null) {
            return 'green';
        }

        // today itself is still fine, the order can go out today
        return $reorder->toDateString() < $today->toDateString() ? 'red' : 'amber';
    }

    /**
     * @return array<int, string>
     */
    private function assumptions(float $burnPerWeek, int $reorderLeadWeeks): array
    {
        return [
            'Burn rate: '.rtrim(rtrim(number_format($burnPerWeek, 2), '0'), '.').' PD per week, the average of the last '.BurnRate::WINDOW_WEEKS.' weeks.',
            'The same pace continues; weekends and public holidays burn nothing.',
            'Planned vacations of the assigned consultants slow the pace down on those days.',
            'Purchasing needs '.$reorderLeadWeeks.' weeks to place a follow-up order.',
        ];
    }
}
