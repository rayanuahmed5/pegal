<?php

namespace App\Domain;

use App\Models\Booking;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

// the monthly proof of work finance checks against the supplier's invoice
// one row is one person on one order for one month, only sums, never single days or times
// (role matrix and privacy concept, docs/roles-and-privacy.md)
class ProofOfWork
{
    /**
     * @return Collection<int, array{order: Order, consultant: User, submitted: float, approved: float, rejected: float}>
     */
    public function monthlyRows(CarbonImmutable $month): Collection
    {
        $sums = Booking::query()
            ->select('order_id', 'user_id')
            ->selectRaw("SUM(CASE WHEN status = 'submitted' THEN hours ELSE 0 END) AS submitted_hours")
            ->selectRaw("SUM(CASE WHEN status = 'approved' THEN hours ELSE 0 END) AS approved_hours")
            ->selectRaw("SUM(CASE WHEN status = 'rejected' THEN hours ELSE 0 END) AS rejected_hours")
            ->whereBetween('worked_on', $this->monthRange($month))
            ->groupBy('order_id', 'user_id')
            ->get();

        // names and orders in two queries, not one per row
        $orders = Order::query()->whereIn('id', $sums->pluck('order_id'))->get()->keyBy('id');
        $consultants = User::query()->with('supplier')->whereIn('id', $sums->pluck('user_id'))->get()->keyBy('id');

        return $sums
            ->map(fn ($row) => [
                'order' => $orders[$row->order_id],
                'consultant' => $consultants[$row->user_id],
                'submitted' => (float) $row->submitted_hours,
                'approved' => (float) $row->approved_hours,
                'rejected' => (float) $row->rejected_hours,
            ])
            ->sortBy(fn (array $row) => $row['order']->number.'|'.$row['consultant']->name)
            ->values();
    }

    /**
     * Approves or rejects every submitted booking of one person on one order in one month.
     * Hours that were decided before stay as they are, a decision is not changed behind anybody's back.
     * Rejected hours give their budget back (the booking rules ignore rejected bookings).
     *
     * @return int how many bookings were decided
     *
     * @throws NothingToDecide when no submitted hours are left in that month
     */
    public function decide(Order $order, User $consultant, CarbonImmutable $month, BookingStatus $decision): int
    {
        if ($decision === BookingStatus::Submitted) {
            throw new InvalidArgumentException('A decision is either approved or rejected.');
        }

        $decided = Booking::query()
            ->where('order_id', $order->id)
            ->where('user_id', $consultant->id)
            ->whereBetween('worked_on', $this->monthRange($month))
            ->where('status', BookingStatus::Submitted->value)
            ->update(['status' => $decision->value]);

        if ($decided === 0) {
            throw new NothingToDecide(sprintf(
                'Nothing to decide: no submitted hours of %s on order %s in %s.',
                $consultant->name,
                $order->number,
                $month->format('F Y'),
            ));
        }

        return $decided;
    }

    // first and last day of the month as Y-m-d, the dates in the table have no time part
    private function monthRange(CarbonImmutable $month): array
    {
        return [$month->startOfMonth()->toDateString(), $month->endOfMonth()->toDateString()];
    }
}
