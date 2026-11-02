<?php

namespace App\Http\Controllers;

use App\Domain\BookingStatus;
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
            ->with('order')
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

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            // checked against the assignments table, so a made-up order id doesn't get through
            'order_id' => ['required', Rule::exists('assignments', 'order_id')->where('user_id', $user->id)],
            'worked_on' => ['required', 'date_format:Y-m-d'],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24', 'multiple_of:0.25'],
            'description' => ['required', 'string', 'max:255'],
        ], [
            'order_id.exists' => 'You are not assigned to that order.',
            'hours.multiple_of' => 'Please use quarter hours, for example 7.5.',
        ]);

        $user->bookings()->create($data + ['status' => BookingStatus::Submitted]);

        return redirect()->route('bookings.index')->with('status', 'Booking saved.');
    }
}
