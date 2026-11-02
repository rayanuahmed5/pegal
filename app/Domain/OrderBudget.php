<?php

namespace App\Domain;

// ordered, used and remaining person-days of one order
// pure numbers on purpose: no database in here, so it is easy to test
final class OrderBudget
{
    // from this share of the budget on, the bar turns amber
    public const WARNING_FROM_PERCENT = 80;

    public function __construct(
        public readonly float $orderedPd,
        public readonly float $usedPd,
    ) {
    }

    // the controller sums the hours in SQL, here they become person-days
    public static function fromHours(float $orderedPd, float $usedHours): self
    {
        return new self($orderedPd, $usedHours / TimeBooking::HOURS_PER_PERSON_DAY);
    }

    public function remainingPd(): float
    {
        return max($this->orderedPd - $this->usedPd, 0.0);
    }

    // capped at 100 so the bar never runs out of its box
    public function percentUsed(): float
    {
        if ($this->orderedPd <= 0) {
            return 100.0;
        }

        return min(round($this->usedPd / $this->orderedPd * 100, 1), 100.0);
    }

    // ok, warning or full, the view turns this into green, amber or red
    public function level(): string
    {
        $percent = $this->percentUsed();

        if ($percent >= 100) {
            return 'full';
        }

        return $percent >= self::WARNING_FROM_PERCENT ? 'warning' : 'ok';
    }
}
