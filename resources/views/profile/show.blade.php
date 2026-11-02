@extends('layouts.app')

@section('title', 'My profile · Pegel')

@section('content')
    <h1>My profile</h1>
    <p class="muted">Your account details. Only an administrator can change your name, email or role.</p>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    <div class="card profile-card">
        <span class="tile-icon tile-icon-big"><x-icon name="user" /></span>
        <dl class="details">
            <div><dt>Name</dt><dd>{{ $user->name }}</dd></div>
            <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
            <div><dt>Role</dt><dd>{{ $user->role->label() }}</dd></div>
            @if ($user->supplier)
                <div><dt>Supplier</dt><dd>{{ $user->supplier->name }}</dd></div>
            @endif
            <div><dt>Member since</dt><dd>{{ $user->created_at?->format('j M Y') ?? '–' }}</dd></div>
        </dl>
    </div>

    <div class="card">
        <h2><x-icon name="key" /> Change password</h2>

        <form method="POST" action="{{ route('profile.password') }}">
            @csrf
            @method('PUT')

            <label for="current_password">Current password</label>
            <input id="current_password" type="password" name="current_password" autocomplete="current-password" required>
            @error('current_password')
                <p class="error">{{ $message }}</p>
            @enderror

            <label for="password">New password</label>
            <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required>
            <p class="muted small">At least 8 characters.</p>
            @error('password')
                <p class="error">{{ $message }}</p>
            @enderror

            <label for="password_confirmation">Repeat new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>

            <button type="submit">Change password</button>
        </form>
    </div>
@endsection
