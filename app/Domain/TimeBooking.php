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

    /**
     * @throws BookingRejected when a rule is broken, nothing is saved then
     */
    public function book(User $consultant, Order $order, string $day, float $hours, string $description): Booking
    {
        // a transaction with the rows locked, so two bookings at the same moment
        // can't both squeeze into the last bit of budget or the last hours of the day
        return DB::transaction(function () use ($consultant, $order, $day, $hours, $description) {
            // always lock in the same order (person, then order) so two requests can't block each other
            $consultant = User::query()->lockForUpdate()->findOrFail($consultant->id);
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            $this->checkAssigned($consultant, $order);
            $this->checkOrderPeriod($order, $day);
            $this->checkNoAbsence($consultant, $day);
            $this->checkDailyLimit($consultant, $day, $hours);
            $this->checkBudget($order, $hours);

            return $consultant->bookings()->create([
                'order_id' => $order->id,
                'worked_on' => $day,
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
