@extends('layouts.app')

@section('title', 'Book time · Pegel')

@section('content')
    <h1>Book time</h1>
    <p class="muted">Pick the order, the day and how long you worked.</p>

    <div class="card">
        @error('booking')
            <p class="alert" role="alert">{{ $message }}</p>
        @enderror

        @if ($orders->isEmpty())
            <p>You are not assigned to an order yet, so there is nothing to book on.</p>
        @else
            <form method="POST" action="{{ route('bookings.store') }}">
                @csrf

                <label for="order_id">Order</label>
                <select id="order_id" name="order_id" required>
                    @foreach ($orders as $order)
                        <option value="{{ $order->id }}" @selected(old('order_id') == $order->id)>
                            {{ $order->number }} · {{ $order->title }}
                        </option>
                    @endforeach
                </select>
                @error('order_id')
                    <p class="error">{{ $message }}</p>
                @enderror

                <label for="worked_on">Day</label>
                <input id="worked_on" type="date" name="worked_on" value="{{ old('worked_on', now()->toDateString()) }}" required>
                @error('worked_on')
                    <p class="error">{{ $message }}</p>
                @enderror

                <label for="starts_at">From (optional)</label>
                <input id="starts_at" type="time" name="starts_at" step="900" value="{{ old('starts_at') }}">
                @error('starts_at')
                    <p class="error">{{ $message }}</p>
                @enderror

                <label for="ends_at">To (optional)</label>
                <input id="ends_at" type="time" name="ends_at" step="900" value="{{ old('ends_at') }}">
                <p class="muted small">With both times we calculate the hours and also check overlaps and the 11 hours of rest. Only you can see these times.</p>
                @error('ends_at')
                    <p class="error">{{ $message }}</p>
                @enderror

                <label for="hours">Hours</label>
                <input id="hours" type="number" name="hours" step="0.25" min="0.25" max="24" value="{{ old('hours', '8') }}">
                <p class="muted small">Quarter hours are fine, for example 7.5.</p>
                @error('hours')
                    <p class="error">{{ $message }}</p>
                @enderror

                <label for="description">What did you do?</label>
                <input id="description" type="text" name="description" maxlength="255" value="{{ old('description') }}" required>
                @error('description')
                    <p class="error">{{ $message }}</p>
                @enderror

                <button type="submit">Save booking</button>
                <a class="cancel" href="{{ route('bookings.index') }}">Cancel</a>
            </form>
        @endif
    </div>
@endsection
