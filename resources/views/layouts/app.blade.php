<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Pegel')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    {{-- runs before the page is drawn, so a dark page does not flash white first --}}
    <script>
        (function () {
            var theme = 'light';
            try {
                theme = localStorage.getItem('pegel-theme')
                    || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            } catch (e) {}
            document.documentElement.setAttribute('data-theme', theme === 'dark' ? 'dark' : 'light');
        })();
    </script>
    <link rel="stylesheet" href="{{ asset('css/pegal.css') }}">
    <script src="{{ asset('js/theme.js') }}" defer></script>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}">
            <img src="{{ asset('favicon.svg') }}" alt="" width="24" height="24"> Pegel
        </a>

        <div class="topbar-right">
        <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Switch to dark mode">
            <span class="when-light"><x-icon name="moon" /> Dark</span>
            <span class="when-dark"><x-icon name="sun" /> Light</span>
        </button>

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
        </div>
    </header>

    {{-- most pages sit in one narrow column, the landing page swaps this for full-width bands --}}
    <main class="@yield('main_class', 'page')">
        @yield('content')
    </main>
</body>
</html>
