<?php

namespace App\Http\Controllers;

use App\Domain\BookingRejected;
use App\Domain\TimeBooking;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// a consultant's own bookings (the routes only let consultants in)
class BookingController extends Controller
{
    // newest first, the latest 50 are plenty for a start
    public function index(Request $request): View
    {
        $bookings = $request->user()->bookings()
            ->with(['order', 'decidedBy'])
            ->orderByDesc('worked_on')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('bookings.index', ['bookings' => $bookings]);
    }

    public function create(Request $request): View
    {
        // only the orders this person is assigned to show up in the list
        $orders = $request->user()->orders()->orderBy('number')->get();

        return view('bookings.create', ['orders' => $orders]);
    }

    public function store(Request $request, TimeBooking $timeBooking): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            // checked against the assignments table, so a made-up order id doesn't get through
            'order_id' => ['required', Rule::exists('assignments', 'order_id')->where('user_id', $user->id)],
            'worked_on' => ['required', 'date_format:Y-m-d'],
            // start and end are optional (ADR 0008), but only as a pair
            'starts_at' => ['nullable', 'required_with:ends_at', 'date_format:H:i'],
            'ends_at' => ['nullable', 'required_with:starts_at', 'date_format:H:i'],
            // with start and end the hours are worked out from them
            'hours' => ['nullable', 'required_without:starts_at', 'numeric', 'min:0.25', 'max:24', 'multiple_of:0.25'],
            'description' => ['required', 'string', 'max:255'],
        ], [
            'order_id.exists' => 'You are not assigned to that order.',
            'starts_at.required_with' => 'Please give both a start and an end time, or neither.',
            'ends_at.required_with' => 'Please give both a start and an end time, or neither.',
            'hours.multiple_of' => 'Please use quarter hours, for example 7.5.',
        ]);

        $startsAt = $data['starts_at'] ?? null;
        $endsAt = $data['ends_at'] ?? null;
        $hours = (float) ($data['hours'] ?? 0);

        // times win over the typed hours, a wrong pair is caught by the rules below
        if ($startsAt !== null && $endsAt !== null) {
            $hours = ($this->minutes($endsAt) - $this->minutes($startsAt)) / 60;
        }

        // the rules live in TimeBooking, here we only pass on its answer
        try {
            $timeBooking->book(
                $user,
                Order::findOrFail($data['order_id']),
                $data['worked_on'],
                $hours,
                $data['description'],
                $startsAt,
                $endsAt,
            );
        } catch (BookingRejected $e) {
            return back()->withInput()->withErrors(['booking' => $e->getMessage()]);
        }

        return redirect()->route('bookings.index')->with('status', 'Booking saved.');
    }

    // "08:30" -> minutes since midnight
    private function minutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $hour * 60 + $minute;
    }
}
