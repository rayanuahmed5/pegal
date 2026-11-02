@extends('layouts.app')

@section('title', 'Edit ' . $user->name . ' · Pegal')

@section('content')
    @php($isMe = $user->is(auth()->user()))

    <p><a class="cancel back" href="{{ route('admin.dashboard') }}">← Back to all users</a></p>
    <h1>{{ $user->name }}</h1>
    <p class="muted">{{ $user->role->label() }} · {{ $bookings }} {{ $bookings === 1 ? 'booking' : 'bookings' }}</p>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    {{-- profile details --}}
    <div class="card">
        <h2><x-icon name="pencil" /> Profile</h2>

        @error('profile')
            <p class="alert">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')

            <label for="name">Name</label>
            <input id="name" type="text" name="name" maxlength="255" value="{{ old('name', $user->name) }}" required>
            @error('name')
                <p class="error">{{ $message }}</p>
            @enderror

            <label for="email">Email</label>
            <input id="email" type="email" name="email" maxlength="255" value="{{ old('email', $user->email) }}" required>
            @error('email')
                <p class="error">{{ $message }}</p>
            @enderror

            <label for="role">Role</label>
            <select id="role" name="role" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(old('role', $user->role->value) === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
            @if ($isMe)
                <p class="muted small">You cannot change your own role.</p>
            @endif

            <label for="supplier_id">Supplier (consultants only)</label>
            <select id="supplier_id" name="supplier_id">
                <option value="">–</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $user->supplier_id) === (string) $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>

            <button type="submit">Save changes</button>
        </form>
    </div>

    {{-- password reset, no old password needed --}}
    <div class="card">
        <h2><x-icon name="key" /> Reset password</h2>
        <p class="muted small">Sets a new password right away. Tell the person in a safe way, we never show it again.</p>

        <form method="POST" action="{{ route('admin.users.password', $user) }}">
            @csrf
            @method('PUT')

            <label for="password">New password</label>
            <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required>
            @error('password')
                <p class="error">{{ $message }}</p>
            @enderror

            <label for="password_confirmation">Repeat new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>

            <button type="submit">Reset password</button>
        </form>
    </div>

    {{-- deleting, the server checks the rules again whatever this page shows --}}
    <div class="card danger-zone">
        <h2><x-icon name="trash" /> Delete account</h2>

        @error('delete')
            <p class="alert">{{ $message }}</p>
        @enderror

        @if ($isMe)
            <p class="muted">You cannot delete your own account.</p>
        @elseif ($bookings > 0)
            <p class="muted">
                {{ $user->name }} has {{ $bookings }} {{ $bookings === 1 ? 'booking' : 'bookings' }}.
                They are proof of work for invoices, so this account cannot be deleted.
            </p>
        @else
            <p class="muted">The account, its order assignments and planned absences are removed for good.</p>
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                  onsubmit="return confirm('Delete {{ addslashes($user->name) }} for good?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="danger">Delete {{ $user->name }}</button>
            </form>
        @endif
    </div>
@endsection
