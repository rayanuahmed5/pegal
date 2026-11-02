@extends('layouts.app')

@section('title', 'Home · Pegal')

@section('content')
    <h1>Hello, {{ $user->name }}</h1>
    <p class="muted">
        You are signed in as <strong>{{ $user->role->label() }}</strong>@if ($user->supplier) at {{ $user->supplier->name }}@endif.
    </p>

    <div class="card">
        <h2>What you do here</h2>
        <p>{{ $user->role->intro() }}</p>

        @if ($user->role === \App\Domain\Role::Consultant)
            <p>
                <a class="button" href="{{ route('bookings.create') }}">Book time</a>
                <a class="cancel" href="{{ route('bookings.index') }}">My bookings</a>
            </p>
        @else
            <p>
                <a class="button" href="{{ route('orders.index') }}">Orders and budget</a>
                @if (in_array($user->role, [\App\Domain\Role::ProjectLead, \App\Domain\Role::Purchasing], true))
                    <a class="cancel" href="{{ route('follow-ups.index') }}">Follow-up orders</a>
                @endif
                @if ($user->role === \App\Domain\Role::Finance)
                    <a class="cancel" href="{{ route('proof-of-work.index') }}">Proof of work</a>
                @endif
            </p>
            <p class="muted small">Forecast and approvals are being built, one step at a time.</p>
        @endif
    </div>
@endsection
