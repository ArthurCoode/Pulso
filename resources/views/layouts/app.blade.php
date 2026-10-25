<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Pulso')</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%231b2a35'/%3E%3Cpath d='M4 17h6l3-9 5 16 3-9h7' fill='none' stroke='%23e9eef2' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
    {{-- O ?v= muda quando o CSS muda, então o navegador nunca usa uma versão antiga. --}}
    <link rel="stylesheet" href="{{ asset('css/pulso.css') }}?v={{ filemtime(public_path('css/pulso.css')) }}">
    @stack('head')
</head>
<body>
    <header class="topbar">
        <div class="wrap">
            <a class="brand" href="{{ route('monitors.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 13h5l2.5-7 4 14 2.5-7H22"/></svg>
                Pulso
            </a>
            <nav class="nav" aria-label="Principal">
                <a href="{{ route('monitors.index') }}" @if (request()->routeIs('monitors.index')) aria-current="page" @endif>Monitores</a>
                <a href="{{ route('status') }}" target="_blank" rel="noopener">Status page</a>
                <a class="btn primary small" href="{{ route('monitors.create') }}">Novo monitor</a>
            </nav>
        </div>
    </header>

    <main class="wrap">
        @if (session('status'))
            <div class="flash s-up" role="status">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
