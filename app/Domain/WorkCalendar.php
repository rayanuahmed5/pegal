<?php

namespace App\Domain;

use Carbon\CarbonImmutable;

// which days count for the forecast: weekends and public holidays never do,
// and a consultant on vacation doesn't burn budget
// plain arrays in, no database in here, the controller loads the rows and hands them over
final class WorkCalendar
{
    /** @var array<string, true> holidays as 'Y-m-d' keys, so a lookup is quick */
    private array $holidays = [];

    /**
     * @param  array<int, string>  $holidays  dates as 'Y-m-d'
     * @param  array<int, array{user_id: int, from: string, to: string}>  $absences  first and last day, both count
     */
    public function __construct(array $holidays = [], private readonly array $absences = [])
    {
        foreach ($holidays as $day) {
            $this->holidays[$day] = true;
        }
    }

    public function isWorkingDay(string $day): bool
    {
        return ! CarbonImmutable::parse($day)->isWeekend() && ! isset($this->holidays[$day]);
    }

    /**
     * How much of the team can work on this day: 1 means everybody, 0.5 half of them, 0 nobody.
     * Only absences of people assigned to the order matter.
     *
     * @param  array<int, int>  $consultantIds  the people assigned to the order
     */
    public function availableShare(string $day, array $consultantIds): float
    {
        if ($consultantIds === [] || ! $this->isWorkingDay($day)) {
            return 0.0;
        }

        $absent = 0;
        foreach ($consultantIds as $id) {
            if ($this->isAbsent($id, $day)) {
                $absent++;
            }
        }

        return (count($consultantIds) - $absent) / count($consultantIds);
    }

    // both the first and the last day are counted
    public function workingDaysBetween(string $from, string $to): int
    {
        $count = 0;

        for ($day = CarbonImmutable::parse($from); $day->toDateString() <= $to; $day = $day->addDay()) {
            if ($this->isWorkingDay($day->toDateString())) {
                $count++;
            }
        }

        return $count;
    }

    private function isAbsent(int $userId, string $day): bool
    {
        foreach ($this->absences as $absence) {
            // plain Y-m-d strings compare correctly, same trick as in TimeBooking
            if ($absence['user_id'] === $userId && $day >= $absence['from'] && $day <= $absence['to']) {
                return true;
            }
        }

        return false;
    }
}
