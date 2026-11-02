@extends('layouts.app')

@section('title', 'Proof of work · Pegal')

@section('content')
    @php
        // 8.00 shows as 8, 7.50 as 7.5
        $hours = fn (float $value) => rtrim(rtrim(number_format($value, 2), '0'), '.');
    @endphp

    <h1>Proof of work</h1>
    <p class="muted">
        Hours per person and order for one month, to check against the supplier's invoice.
        Only sums are shown, no single days and no times.
    </p>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif
    @error('decision')
        <p class="alert" role="alert">{{ $message }}</p>
    @enderror

    <p class="month-nav">
        <a href="{{ route('proof-of-work.index', ['month' => $previous]) }}">&larr; {{ $month->subMonth()->format('M Y') }}</a>
        <strong>{{ $month->format('F Y') }}</strong>
        <a href="{{ route('proof-of-work.index', ['month' => $next]) }}">{{ $month->addMonth()->format('M Y') }} &rarr;</a>
    </p>

    <div class="card">
        @if ($rows->isEmpty())
            <p class="muted">Nobody booked time in {{ $month->format('F Y') }}.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Person</th>
                        <th>Order</th>
                        <th class="num">To check (h)</th>
                        <th class="num">Approved (h)</th>
                        <th class="num">Rejected (h)</th>
                        <th>Decision</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>
                                {{ $row['consultant']->name }}<br>
                                <span class="muted small">{{ $row['consultant']->supplier?->name }}</span>
                            </td>
                            <td>
                                <strong>{{ $row['order']->number }}</strong><br>
                                <span class="muted small">{{ $row['order']->title }}</span>
                            </td>
                            <td class="num">{{ $hours($row['submitted']) }}</td>
                            <td class="num">{{ $hours($row['approved']) }}</td>
                            <td class="num">{{ $hours($row['rejected']) }}</td>
                            <td class="row-actions">
                                @if ($row['submitted'] > 0)
                                    @foreach (['approve' => 'Approve', 'reject' => 'Reject'] as $decision => $label)
                                        <form method="POST" action="{{ route('proof-of-work.decide') }}">
                                            @csrf
                                            <input type="hidden" name="order_id" value="{{ $row['order']->id }}">
                                            <input type="hidden" name="user_id" value="{{ $row['consultant']->id }}">
                                            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                                            <input type="hidden" name="decision" value="{{ $decision }}">
                                            @if ($decision === 'reject')
                                                <input type="text" name="reason" maxlength="255" placeholder="Reason (required)" aria-label="Reason for rejecting" required>
                                            @endif
                                            <button type="submit" class="{{ $decision === 'reject' ? 'danger' : '' }}">{{ $label }}</button>
                                        </form>
                                    @endforeach
                                @else
                                    <span class="muted small">All decided</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
