<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Pegal')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/pegal.css') }}">
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}">
            <img src="{{ asset('favicon.svg') }}" alt="" width="24" height="24"> Pegal
        </a>

        @auth
            <div class="who">
                <a class="who-name" href="{{ route('profile.show') }}" title="My profile">
                    <x-icon name="user" /> {{ auth()->user()->name }}
                </a>
                <span class="role">{{ auth()->user()->role->label() }}</span>

                {{-- logging out is a POST so a stray link can't sign somebody out --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="link">Sign out</button>
                </form>
            </div>
        @endauth

        {{-- guests only need one way in, the login page hides it because they are already there --}}
        @guest
            @unless (request()->routeIs('login'))
                <a class="topbar-signin" href="{{ route('login') }}">Sign in</a>
            @endunless
        @endguest
    </header>

    {{-- most pages sit in one narrow column, the landing page swaps this for full-width bands --}}
    <main class="@yield('main_class', 'page')">
        @yield('content')
    </main>
</body>
</html>
