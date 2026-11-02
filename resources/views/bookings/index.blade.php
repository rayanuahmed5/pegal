@extends('layouts.app')

@section('title', 'My bookings · Pegal')

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
                        <td>{{ $booking->worked_on->format('d M Y') }}</td>
                        <td>{{ $booking->order->number }} · {{ $booking->order->title }}</td>
                        <td>{{ (float) $booking->hours }}</td>
                        <td>{{ $booking->description }}</td>
                        <td>
                            <span class="status status-{{ $booking->status->value }}">{{ ucfirst($booking->status->value) }}</span>
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
@endsection
