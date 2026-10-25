<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Recarrega sozinha a cada minuto, o mesmo tempo do cache. --}}
    <meta http-equiv="refresh" content="60">
    <title>{{ $title }}</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%231b2a35'/%3E%3Cpath d='M4 17h6l3-9 5 16 3-9h7' fill='none' stroke='%23e9eef2' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="{{ asset('css/pulso.css') }}?v={{ filemtime(public_path('css/pulso.css')) }}">
</head>
<body class="public">
@use('App\Support\Format')
@php
    // Datas chegam como timestamp (o cache guarda só valores simples).
    $when = fn (?int $timestamp, string $format = 'd/m/Y H:i') =>
        Format::dt($timestamp ? \Illuminate\Support\Carbon::createFromTimestamp($timestamp) : null, $format);
@endphp
<div class="wrap">
    <header class="public-head">
        <h1>{{ $title }}</h1>
        <p class="muted">{{ $description }}</p>
    </header>

    @if ($overall === null)
        <div class="card empty" style="margin-top: 26px;">Nenhum serviço público configurado.</div>
    @else
        <div class="state s-{{ $overall['class'] }}">
            <span class="beat" aria-hidden="true"></span>
            <span>{{ $overall['label'] }}</span>
        </div>

        <div class="card">
            @foreach ($monitors as $monitor)
                <section class="svc">
                    <div class="svc-head">
                        <span class="svc-name">{{ $monitor['name'] }}</span>
                        <span class="chip s-{{ $monitor['state'] }}"><i class="dot"></i>{{ $monitor['label'] }}</span>
                    </div>

                    <div class="bars" role="img" aria-label="Disponibilidade diária nos últimos 90 dias">
                        @foreach ($monitor['days'] as $day)
                            <span class="bar {{ $day['class'] }}"
                                  title="{{ $day['label'] }}: {{ $day['pct'] === null ? 'sem dados' : Format::percent($day['pct']).' de disponibilidade' }}"></span>
                        @endforeach
                    </div>

                    <div class="bars-foot">
                        <span>90 dias atrás</span>
                        <span>{{ Format::percent($monitor['uptime']) }} de disponibilidade</span>
                        <span>Hoje</span>
                    </div>
                </section>
            @endforeach
        </div>

        <h2>Incidentes recentes</h2>
        <div class="card">
            @forelse ($incidents as $incident)
                <div class="incident">
                    <strong>{{ $incident['monitor'] }}</strong>:
                    {{ $incident['resolved_at'] ? 'indisponibilidade resolvida' : 'indisponibilidade em andamento' }}
                    <div class="muted">
                        {{ $when($incident['started_at']) }}
                        @if ($incident['resolved_at'])
                            até {{ $when($incident['resolved_at']) }}
                        @endif
                        ({{ Format::duration($incident['seconds']) }})
                    </div>
                </div>
            @empty
                <div class="empty">Nenhum incidente nos últimos 14 dias.</div>
            @endforelse
        </div>

        <p class="public-foot">
            Atualizado em {{ $when($generated_at, 'd/m/Y H:i:s') }}. Esta página recarrega sozinha a cada minuto.
        </p>
    @endif
</div>
</body>
</html>
