@extends('layouts.app')

@section('title', 'Add user · Pegal')

@section('content')
    <h1>Add user</h1>
    <p class="muted">The new person can sign in with this email and password right away.</p>

    <div class="card">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <label for="name">Name</label>
            <input id="name" type="text" name="name" maxlength="255" value="{{ old('name') }}" required>
            @error('name')
                <p class="error">{{ $message }}</p>
            @enderror

            <label for="email">Email</label>
            <input id="email" type="email" name="email" maxlength="255" value="{{ old('email') }}" required>
            @error('email')
                <p class="error">{{ $message }}</p>
            @enderror

            <label for="role">Role</label>
            <select id="role" name="role" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
            @error('role')
                <p class="error">{{ $message }}</p>
            @enderror

            <label for="supplier_id">Supplier (consultants only)</label>
            <select id="supplier_id" name="supplier_id">
                <option value="">–</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
            @error('supplier_id')
                <p class="error">{{ $message }}</p>
            @enderror

            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required>
            <p class="muted small">At least 8 characters. Tell the person in a safe way, we never show it again.</p>
            @error('password')
                <p class="error">{{ $message }}</p>
            @enderror

            <label for="password_confirmation">Repeat password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>

            <button type="submit">Add user</button>
            <a class="cancel" href="{{ route('admin.dashboard') }}">Cancel</a>
        </form>
    </div>
@endsection
