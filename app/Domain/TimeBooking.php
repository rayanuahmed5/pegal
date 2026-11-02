<?php

namespace App\Domain;

use App\Models\Booking;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

// the one place that decides "may this person book these hours on this order?"
// controllers just call book() and show the message if it says no
class TimeBooking
{
    // 1 person-day is 8 hours (docs/domain.md)
    public const HOURS_PER_PERSON_DAY = 8;

    // German Working Time Act: no more than 10 hours a day
    public const MAX_HOURS_PER_DAY = 10;

    // and at least 11 hours of rest between two working days
    public const MIN_REST_HOURS = 11;

    /**
     * @throws BookingRejected when a rule is broken, nothing is saved then
     */
    public function book(
        User $consultant,
        Order $order,
        string $day,
        float $hours,
        string $description,
        ?string $startsAt = null,
        ?string $endsAt = null,
    ): Booking {
        // a transaction with the rows locked, so two bookings at the same moment
        // can't both squeeze into the last bit of budget or the last hours of the day
        return DB::transaction(function () use ($consultant, $order, $day, $hours, $description, $startsAt, $endsAt) {
            // always lock in the same order (person, then order) so two requests can't block each other
            $consultant = User::query()->lockForUpdate()->findOrFail($consultant->id);
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            $this->checkAssigned($consultant, $order);
            $this->checkOrderPeriod($order, $day);
            $this->checkNoAbsence($consultant, $day);
            $this->checkTimes($hours, $startsAt, $endsAt);
            $this->checkNoOverlap($consultant, $day, $startsAt, $endsAt);
            $this->checkRestPeriod($consultant, $day, $startsAt, $endsAt);
            $this->checkDailyLimit($consultant, $day, $hours);
            $this->checkBudget($order, $hours);

            return $consultant->bookings()->create([
                'order_id' => $order->id,
                'worked_on' => $day,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'hours' => $hours,
                'description' => $description,
                'status' => BookingStatus::Submitted,
            ]);
        });
    }

    private function checkAssigned(User $consultant, Order $order): void
    {
        if (! $order->consultants()->whereKey($consultant->id)->exists()) {
            throw new BookingRejected("Booking rejected: you are not assigned to order {$order->number}.");
        }
    }

    private function checkOrderPeriod(Order $order, string $day): void
    {
        // plain Y-m-d strings compare correctly, no time zones to worry about
        $from = $order->starts_on->toDateString();
        $to = $order->ends_on->toDateString();

        if ($day < $from || $day > $to) {
            throw new BookingRejected(sprintf(
                'Booking rejected: %s is outside the period of order %s (%s to %s).',
                $this->niceDay($day),
                $order->number,
                $this->niceDay($from),
                $this->niceDay($to),
            ));
        }
    }

    // nobody works on a day they have planned vacation, the same absences also slow the forecast down
    private function checkNoAbsence(User $consultant, string $day): void
    {
        $absence = $consultant->absences()
            ->whereDate('starts_on', '<=', $day)
            ->whereDate('ends_on', '>=', $day)
            ->first();

        if ($absence !== null) {
            throw new BookingRejected(sprintf(
                'Booking rejected: you have a planned absence from %s to %s, which includes %s.',
                $this->niceDay($absence->starts_on->toDateString()),
                $this->niceDay($absence->ends_on->toDateString()),
                $this->niceDay($day),
            ));
        }
    }

    // start and end come as a pair, and they have to agree with the hours
    private function checkTimes(float $hours, ?string $startsAt, ?string $endsAt): void
    {
        if ($startsAt === null && $endsAt === null) {
            return;
        }

        if ($startsAt === null || $endsAt === null) {
            throw new BookingRejected('Booking rejected: please give both a start and an end time, or neither.');
        }

        $minutes = $this->minutes($endsAt) - $this->minutes($startsAt);

        if ($minutes <= 0) {
            throw new BookingRejected('Booking rejected: the end time must be after the start time on the same day.');
        }

        if ($minutes % 15 !== 0) {
            throw new BookingRejected('Booking rejected: please use quarter hours for start and end, for example 08:30.');
        }

        if (abs($minutes / 60 - $hours) > 0.001) {
            throw new BookingRejected(sprintf(
                'Booking rejected: %s to %s is %s hours, but you entered %s hours.',
                $this->clock($startsAt),
                $this->clock($endsAt),
                $this->plain($minutes / 60),
                $this->plain($hours),
            ));
        }
    }

    // one person can't work in two places at once
    private function checkNoOverlap(User $consultant, string $day, ?string $startsAt, ?string $endsAt): void
    {
        if ($startsAt === null || $endsAt === null) {
            return;
        }

        $others = Booking::query()
            ->where('user_id', $consultant->id)
            ->where('worked_on', $day)
            ->whereNotNull('starts_at')
            ->where('status', '!=', BookingStatus::Rejected->value)
            ->get();

        foreach ($others as $other) {
            // two ranges overlap when each starts before the other one ends
            if ($this->minutes($startsAt) < $this->minutes($other->ends_at) && $this->minutes($endsAt) > $this->minutes($other->starts_at)) {
                throw new BookingRejected(sprintf(
                    'Booking rejected: %s to %s overlaps your booking from %s to %s on %s.',
                    $this->clock($startsAt),
                    $this->clock($endsAt),
                    $this->clock($other->starts_at),
                    $this->clock($other->ends_at),
                    $this->niceDay($day),
                ));
            }
        }
    }

    // 11 hours of rest, looked at from both sides: the day before and the day after
    private function checkRestPeriod(User $consultant, string $day, ?string $startsAt, ?string $endsAt): void
    {
        if ($startsAt === null || $endsAt === null) {
            return;
        }

        $dayBefore = CarbonImmutable::parse($day)->subDay()->toDateString();
        $dayAfter = CarbonImmutable::parse($day)->addDay()->toDateString();

        $lastEnd = $this->timedBookings($consultant, $dayBefore)->max('ends_at');
        if ($lastEnd !== null) {
            $restMinutes = 24 * 60 + $this->minutes($startsAt) - $this->minutes($lastEnd);

            if ($restMinutes < self::MIN_REST_HOURS * 60) {
                throw new BookingRejected(sprintf(
                    'Booking rejected: only %s hours of rest between work ending at %s on %s and starting at %s on %s, you need at least %d hours.',
                    $this->plain($restMinutes / 60),
                    $this->clock($lastEnd),
                    $this->niceDay($dayBefore),
                    $this->clock($startsAt),
                    $this->niceDay($day),
                    self::MIN_REST_HOURS,
                ));
            }
        }

        $firstStart = $this->timedBookings($consultant, $dayAfter)->min('starts_at');
        if ($firstStart !== null) {
            $restMinutes = 24 * 60 + $this->minutes($firstStart) - $this->minutes($endsAt);

            if ($restMinutes < self::MIN_REST_HOURS * 60) {
                throw new BookingRejected(sprintf(
                    'Booking rejected: only %s hours of rest between work ending at %s on %s and your booking starting at %s on %s, you need at least %d hours.',
                    $this->plain($restMinutes / 60),
                    $this->clock($endsAt),
                    $this->niceDay($day),
                    $this->clock($firstStart),
                    $this->niceDay($dayAfter),
                    self::MIN_REST_HOURS,
                ));
            }
        }
    }

    private function timedBookings(User $consultant, string $day)
    {
        return Booking::query()
            ->where('user_id', $consultant->id)
            ->where('worked_on', $day)
            ->whereNotNull('starts_at')
            ->where('status', '!=', BookingStatus::Rejected->value)
            ->get();
    }

    // "08:30" or "08:30:00" -> minutes since midnight
    private function minutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $hour * 60 + $minute;
    }

    // "08:30:00" -> "08:30"
    private function clock(string $time): string
    {
        return substr($time, 0, 5);
    }

    private function checkDailyLimit(User $consultant, string $day, float $hours): void
    {
        // counts every order of this person, the limit is per person and day
        $already = $this->hoursNotRejected(Booking::query()
            ->where('user_id', $consultant->id)
            ->where('worked_on', $day));

        if ($already + $hours > self::MAX_HOURS_PER_DAY) {
            $message = sprintf(
                'Booking rejected: that would be %s hours on %s, the limit is %d hours per day.',
                $this->plain($already + $hours),
                $this->niceDay($day),
                self::MAX_HOURS_PER_DAY,
            );

            if ($already > 0) {
                $message .= ' You already booked '.$this->plain($already).' hours that day.';
            }

            throw new BookingRejected($message);
        }
    }

    private function checkBudget(Order $order, float $hours): void
    {
        $budgetHours = (float) $order->budget_pd * self::HOURS_PER_PERSON_DAY;
        $usedHours = $this->hoursNotRejected($order->bookings());
        $leftHours = $budgetHours - $usedHours;

        if ($hours > $leftHours) {
            $leftPd = max($leftHours, 0) / self::HOURS_PER_PERSON_DAY;

            throw new BookingRejected(
                "Booking rejected: only {$this->plain($leftPd)} PD left on order {$order->number}."
            );
        }
    }

    // rejected bookings don't count against the budget or the daily limit
    private function hoursNotRejected($query): float
    {
        return (float) $query->where('status', '!=', BookingStatus::Rejected->value)->sum('hours');
    }

    // 8.00 -> "8", 7.50 -> "7.5", 0.25 -> "0.25"
    private function plain(float $number): string
    {
        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }

    private function niceDay(string $day): string
    {
        return CarbonImmutable::parse($day)->format('j M Y');
    }
}
