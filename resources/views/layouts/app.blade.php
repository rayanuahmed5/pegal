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
    </header>

    <main class="page">
        @yield('content')
    </main>
</body>
</html>
