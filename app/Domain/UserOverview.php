<?php

namespace App\Domain;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// what the administrator sees: every account with its role, the person-days it is assigned
// and the person-days it has booked. Pegel has no prices, so "amount" means person-days (PD).
class UserOverview
{
    public function total(): int
    {
        return User::count();
    }

    /**
     * Number of users per role, every role is in the list even when it has nobody (yet).
     *
     * @return array<string, int> role value => count
     */
    public function countByRole(): array
    {
        $counts = User::query()
            ->select('role', DB::raw('COUNT(*) AS total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        $result = [];
        foreach (Role::cases() as $role) {
            $result[$role->value] = (int) ($counts[$role->value] ?? 0);
        }

        return $result;
    }

    /**
     * assignedPd is the budget of all orders the person is assigned to. Orders are shared with
     * colleagues, so these numbers are not meant to be added up across people.
     * spentPd is what the person booked, rejected bookings do not count (same as the order budget).
     *
     * @return Collection<int, array{user: User, assignedOrders: int, assignedPd: float, spentPd: float}>
     */
    public function rows(): Collection
    {
        // two grouped queries for everybody, not two queries per person
        $assigned = DB::table('assignments')
            ->join('orders', 'orders.id', '=', 'assignments.order_id')
            ->select('assignments.user_id', DB::raw('COUNT(*) AS order_count'), DB::raw('SUM(orders.budget_pd) AS pd'))
            ->groupBy('assignments.user_id')
            ->get()
            ->keyBy('user_id');

        $booked = Booking::query()
            ->select('user_id', DB::raw('SUM(hours) AS booked_hours'))
            ->where('status', '!=', BookingStatus::Rejected->value)
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        return User::query()
            ->with('supplier')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'user' => $user,
                'assignedOrders' => (int) ($assigned[$user->id]->order_count ?? 0),
                'assignedPd' => (float) ($assigned[$user->id]->pd ?? 0),
                'spentPd' => (float) ($booked[$user->id]->booked_hours ?? 0) / TimeBooking::HOURS_PER_PERSON_DAY,
            ])
            ->values();
    }
}
