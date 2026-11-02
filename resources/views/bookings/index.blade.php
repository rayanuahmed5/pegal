@extends('layouts.app')

@section('title', 'My bookings · Pegel')

@section('content')
    <h1>My bookings</h1>
    <p class="muted">The latest 50 entries you booked.</p>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    <p><a class="button" href="{{ route('bookings.create') }}">Book time</a></p>

    <div class="card">
        @if ($bookings->isEmpty())
            <p class="muted">Nothing booked yet.</p>
        @else
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
