@extends('layouts.app')

@section('title', 'Sign in · Pegal')

@section('content')
    <div class="login">
        <h1>Sign in to Pegal</h1>
        <p class="muted">Book working time and keep an eye on the budget of every order.</p>

        <div class="card">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <p class="error">{{ $message }}</p>
                @enderror

                <label for="password">Password</label>
                <input id="password" type="password" name="password" required>

                <label class="check">
                    <input type="checkbox" name="remember" value="1"> Keep me signed in
                </label>

                <button type="submit">Sign in</button>
            </form>
        </div>

        {{-- only on a local machine, so the demo is easy to click through --}}
        @if (app()->environment('local'))
            <div class="card">
                <h2>Demo users</h2>
                <p class="muted">Password for all of them: <strong>pegal-demo</strong></p>
                <table>
                    <tr><th>Role</th><th>Email</th></tr>
                    <tr><td>Consultant</td><td>consultant@pegal.test</td></tr>
                    <tr><td>Project lead</td><td>lead@pegal.test</td></tr>
                    <tr><td>Purchasing</td><td>purchasing@pegal.test</td></tr>
                    <tr><td>Finance</td><td>finance@pegal.test</td></tr>
                </table>
            </div>
        @endif
    </div>
@endsection
