<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Recarrega sozinha a cada minuto, o mesmo tempo do cache. --}}
    <meta http-equiv="refresh" content="60">
    <title>{{ $title }}</title>
    <style>
        :root {
            --bg: #f5f6f8; --card: #ffffff; --text: #1c2230; --muted: #6a7488; --line: #e2e6ed;
            --up: #1a9d57; --warn: #e0a100; --down: #d83a3a; --none: #d5dae3; --paused: #8892a6;
        }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #11141b; --card: #1a1f29; --text: #e6e9f0; --muted: #96a0b4;
                    --line: #2a3140; --none: #2f3747; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text);
               font: 15px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { max-width: 760px; margin: 0 auto; padding: 36px 20px 60px; }
        h1 { font-size: 26px; margin: 0 0 4px; }
        h2 { font-size: 16px; margin: 32px 0 10px; }
        .muted { color: var(--muted); font-size: 13px; }
        .banner { margin: 22px 0; padding: 16px 20px; border-radius: 10px; color: #fff; font-weight: 600; }
        .banner.up { background: var(--up); } .banner.warn { background: var(--warn); }
        .banner.down { background: var(--down); } .banner.unknown { background: var(--paused); }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 10px; }
        .service { padding: 16px 20px; border-bottom: 1px solid var(--line); }
        .service:last-child { border-bottom: none; }
        .service-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 10px; }
        .name { font-weight: 600; }
        .badge { padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; color: #fff; white-space: nowrap; }
        .badge.up { background: var(--up); } .badge.down { background: var(--down); }
        .badge.unknown, .badge.maintenance { background: var(--paused); }
        .bars { display: flex; gap: 2px; height: 34px; }
        .bar { flex: 1; min-width: 2px; border-radius: 2px; }
        .bar.up { background: var(--up); } .bar.warn { background: var(--warn); }
        .bar.down { background: var(--down); } .bar.none { background: var(--none); }
        .foot { display: flex; justify-content: space-between; margin-top: 6px; }
        .incident { padding: 12px 20px; border-bottom: 1px solid var(--line); }
        .incident:last-child { border-bottom: none; }
        .empty { padding: 36px 20px; text-align: center; color: var(--muted); }
        footer { margin-top: 28px; text-align: center; }
    </style>
</head>
<body>
@use('App\Support\Format')
@php
    // Datas chegam como timestamp (o cache guarda só valores simples).
    $when = fn (?int $timestamp, string $format = 'd/m/Y H:i') =>
        Format::dt($timestamp ? \Illuminate\Support\Carbon::createFromTimestamp($timestamp) : null, $format);
@endphp
<main>
    <h1>{{ $title }}</h1>
    <div class="muted">{{ $description }}</div>

    @if ($overall === null)
        <div class="card empty" style="margin-top: 22px;">Nenhum serviço público configurado.</div>
    @else
        <div class="banner {{ $overall['class'] }}">{{ $overall['label'] }}</div>

        <div class="card">
            @foreach ($monitors as $monitor)
                <div class="service">
                    <div class="service-head">
                        <span class="name">{{ $monitor['name'] }}</span>
                        <span class="badge {{ $monitor['state'] }}">{{ $monitor['label'] }}</span>
                    </div>

                    <div class="bars" role="img" aria-label="Disponibilidade diária nos últimos 90 dias">
                        @foreach ($monitor['days'] as $day)
                            <span class="bar {{ $day['class'] }}"
                                  title="{{ $day['label'] }}: {{ $day['pct'] === null ? 'sem dados' : Format::percent($day['pct']).' de disponibilidade' }}"></span>
                        @endforeach
                    </div>

                    <div class="foot muted">
                        <span>90 dias atrás</span>
                        <span>{{ Format::percent($monitor['uptime']) }} de disponibilidade</span>
                        <span>Hoje</span>
                    </div>
                </div>
            @endforeach
        </div>

        <h2>Incidentes recentes</h2>
        <div class="card">
            @forelse ($incidents as $incident)
                <div class="incident">
                    <strong>{{ $incident['monitor'] }}</strong>
                    — {{ $incident['resolved_at'] ? 'Indisponibilidade resolvida' : 'Indisponibilidade em andamento' }}
                    <div class="muted">
                        {{ $when($incident['started_at']) }}
                        @if ($incident['resolved_at'])
                            até {{ $when($incident['resolved_at']) }}
                        @endif
                        · {{ Format::duration($incident['seconds']) }}
                    </div>
                </div>
            @empty
                <div class="empty">Nenhum incidente nos últimos 14 dias.</div>
            @endforelse
        </div>

        <footer class="muted">
            Atualizado em {{ $when($generated_at, 'd/m/Y H:i:s') }} · esta página recarrega sozinha a cada minuto.
        </footer>
    @endif
</main>
</body>
</html>