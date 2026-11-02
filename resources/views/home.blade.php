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
            <p class="muted">The screens for this are being built, one step at a time.</p>
        @endif
    </div>
@endsection
