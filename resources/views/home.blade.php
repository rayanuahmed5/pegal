@extends('layouts.app')

@section('title', 'Home · Pegal')

@section('content')
    @php($role = $user->role)
    @php($Role = \App\Domain\Role::class)

    <h1>Hello, {{ $user->name }}</h1>
    <p class="muted">
        You are signed in as <strong>{{ $role->label() }}</strong>@if ($user->supplier) at {{ $user->supplier->name }}@endif.
        {{ $role->intro() }}
    </p>

    {{-- one tile per thing this role can do, the routes check the role again on the server --}}
    <div class="action-grid">
        @if ($role === $Role::Consultant)
            <a class="action-tile" href="{{ route('bookings.create') }}">
                <span class="tile-icon"><x-icon name="clock" /></span>
                <strong>Book time</strong>
                <span class="muted small">Date, hours and a short note on one of your orders.</span>
            </a>
            <a class="action-tile" href="{{ route('bookings.index') }}">
                <span class="tile-icon"><x-icon name="list" /></span>
                <strong>My bookings</strong>
                <span class="muted small">Everything you booked and whether it was approved.</span>
            </a>
        @elseif ($role === $Role::Admin)
            <a class="action-tile" href="{{ route('admin.dashboard') }}">
                <span class="tile-icon"><x-icon name="users" /></span>
                <strong>Administration</strong>
                <span class="muted small">All accounts with their role and person-days.</span>
            </a>
            <a class="action-tile" href="{{ route('admin.users.create') }}">
                <span class="tile-icon"><x-icon name="plus" /></span>
                <strong>Add user</strong>
                <span class="muted small">Give a new person access to Pegal.</span>
            </a>
        @else
            <a class="action-tile" href="{{ route('orders.index') }}">
                <span class="tile-icon"><x-icon name="gauge" /></span>
                <strong>Orders and budget</strong>
                <span class="muted small">Used and remaining budget, traffic light and forecast.</span>
            </a>
            @if (in_array($role, [$Role::ProjectLead, $Role::Purchasing], true))
                <a class="action-tile" href="{{ route('follow-ups.index') }}">
                    <span class="tile-icon"><x-icon name="bell" /></span>
                    <strong>Follow-up orders</strong>
                    <span class="muted small">Orders that need a reorder soon, most urgent first.</span>
                </a>
            @endif
            @if ($role === $Role::Finance)
                <a class="action-tile" href="{{ route('proof-of-work.index') }}">
                    <span class="tile-icon"><x-icon name="file" /></span>
                    <strong>Proof of work</strong>
                    <span class="muted small">Approve or reject the monthly hours per order.</span>
                </a>
            @endif
        @endif
    </div>
@endsection
