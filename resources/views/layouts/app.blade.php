<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Pegal')</title>
    <link rel="stylesheet" href="{{ asset('css/pegal.css') }}">
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}">Pegal</a>

        @auth
            <div class="who">
                <span>{{ auth()->user()->name }}</span>
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
