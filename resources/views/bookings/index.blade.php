@extends('layouts.app')

@section('title', 'My bookings · Pegel')

@section('content')
    <h1>My bookings</h1>
    <p class="muted">
        @if ($filter->isActive())
            Everything you booked in {{ $filter->label() }}.
        @else
            The latest 50 entries you booked. Pick a month or a year to see more.
        @endif
    </p>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    @if ($filterError)
        <p class="alert" role="alert">{{ $filterError }}</p>
    @endif

    <p><a class="button" href="{{ route('bookings.create') }}">Book time</a></p>

    {{-- a plain GET form, so a filtered list has an address that can be bookmarked --}}
    <form method="GET" action="{{ route('bookings.index') }}" class="filter">
        <label>
            Month
            <select name="month">
                <option value="">All months</option>
                @foreach (range(1, 12) as $number)
                    <option value="{{ $number }}" @selected($filter->month === $number)>{{ \Carbon\Carbon::create(2000, $number, 1)->format('F') }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Year
            <select name="year">
                <option value="">All years</option>
                @foreach ($years as $year)
                    <option value="{{ $year }}" @selected($filter->year === $year)>{{ $year }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit">Filter</button>
        @if ($filter->isActive())
            <a class="cancel" href="{{ route('bookings.index') }}">Clear</a>
        @endif
    </form>

    <div class="card">
        @if ($bookings->isEmpty())
            <p class="muted">{{ $filter->isActive() ? 'Nothing booked in '.$filter->label().'.' : 'Nothing booked yet.' }}</p>
        @else
            @if ($filter->isActive())
                <p class="muted small">{{ $bookings->count() }} {{ \Illuminate\Support\Str::plural('booking', $bookings->count()) }}, {{ rtrim(rtrim(number_format((float) $bookings->sum('hours'), 2), '0'), '.') }} hours in {{ $filter->label() }}</p>
            @endif
            <table>
                <tr>
                    <th>Day</th>
                    <th>Order</th>
                    <th>Hours</th>
                    <th>What you did</th>
                    <th>Status</th>
                </tr>
                @foreach ($bookings as $booking)
                    <tr>
                        <td>
                            {{ $booking->worked_on->format('d M Y') }}
                            @if ($booking->starts_at)
                                <div class="muted small">{{ substr($booking->starts_at, 0, 5) }}–{{ substr($booking->ends_at, 0, 5) }}</div>
                            @endif
                        </td>
                        <td>{{ $booking->order->number }} · {{ $booking->order->title }}</td>
                        <td>{{ (float) $booking->hours }}</td>
                        <td>{{ $booking->description }}</td>
                        <td>
                            <span class="status status-{{ $booking->status->value }}">{{ ucfirst($booking->status->value) }}</span>
                            @if ($booking->decided_at)
                                <div class="muted small">{{ $booking->status->value === 'rejected' ? 'Rejected' : 'Approved' }} by {{ $booking->decidedBy?->name ?? 'finance' }} on {{ $booking->decided_at->format('j M Y') }}</div>
                            @endif
                            @if ($booking->decision_reason)
                                <div class="small">Reason: {{ $booking->decision_reason }}</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
@endsection
