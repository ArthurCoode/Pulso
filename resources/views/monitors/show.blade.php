@extends('layouts.app')

@use('App\Support\Format')

@section('title', $monitor->name.' · Pulso')

@push('head')
    <meta http-equiv="refresh" content="30">
@endpush

@section('content')
    <div class="head">
        <div>
            <h1>{{ $monitor->name }} @include('monitors._badge')</h1>
            <div class="muted">{{ $monitor->method }} {{ $monitor->url }} · a cada {{ $monitor->interval_minutes }} min · espera status {{ $monitor->expected_status }}</div>
        </div>
        <div class="actions">
            <form method="POST" action="{{ route('monitors.toggle', $monitor) }}">
                @csrf
                <button class="btn" type="submit">{{ $monitor->is_active ? 'Pausar' : 'Retomar' }}</button>
            </form>
            <a class="btn" href="{{ route('monitors.edit', $monitor) }}">Editar</a>
            <form method="POST" action="{{ route('monitors.destroy', $monitor) }}"
                  onsubmit="return confirm('Remover este monitor e todo o histórico dele?')">
                @csrf
                @method('DELETE')
                <button class="btn danger" type="submit">Remover</button>
            </form>
        </div>
    </div>

    @if ($monitor->openIncident)
        <div class="alert-down">
            <strong>Incidente em andamento</strong> desde {{ Format::dt($monitor->openIncident->started_at) }}
            ({{ Format::incidentDuration($monitor->openIncident) }}).
            Motivo: {{ $monitor->openIncident->cause ?? 'não informado' }}
        </div>
    @endif

    <div class="stats">
        <div class="card"><div class="num">{{ Format::percent($uptime24) }}</div><div class="muted">Uptime 24h</div></div>
        <div class="card"><div class="num">{{ Format::percent($uptime7) }}</div><div class="muted">Uptime 7 dias</div></div>
        <div class="card"><div class="num">{{ Format::percent($uptime30) }}</div><div class="muted">Uptime 30 dias</div></div>
        <div class="card"><div class="num">{{ $avgResponse !== null ? $avgResponse.' ms' : '—' }}</div><div class="muted">Resposta média 24h</div></div>
    </div>

    <h2>Tempo de resposta</h2>
    <div class="card chart">
        @php
            $values = $series->pluck('response_time_ms')->map(fn ($v) => (int) $v)->all();
            $count = count($values);
        @endphp

        @if ($count > 1)
            @php
                $width = 600;
                $height = 80;
                $max = max($values) ?: 1;
                $points = [];
                foreach ($values as $i => $value) {
                    $x = round($i / ($count - 1) * $width, 1);
                    $y = round($height - ($value / $max) * ($height - 8) - 4, 1);
                    $points[] = $x.','.$y;
                }
            @endphp
            <svg class="spark" viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" role="img"
                 aria-label="Tempo de resposta das últimas checagens">
                <polyline fill="none" stroke="currentColor" stroke-width="2" vector-effect="non-scaling-stroke"
                          points="{{ implode(' ', $points) }}" />
            </svg>
            <div class="muted">Últimas {{ $count }} checagens bem-sucedidas · pico de {{ $max }} ms</div>
        @else
            <div class="muted">Ainda não há checagens bem-sucedidas suficientes para o gráfico.</div>
        @endif
    </div>

    <h2>Incidentes</h2>
    <div class="card table-wrap">
        @if ($incidents->isEmpty())
            <div class="empty">Nenhum incidente registrado.</div>
        @else
            <table>
                <thead><tr><th>Início</th><th>Fim</th><th>Duração</th><th>Motivo</th></tr></thead>
                <tbody>
                    @foreach ($incidents as $incident)
                        <tr>
                            <td>{{ Format::dt($incident->started_at) }}</td>
                            <td>{{ $incident->resolved_at ? Format::dt($incident->resolved_at) : 'Em andamento' }}</td>
                            <td>{{ Format::incidentDuration($incident) }}</td>
                            <td>{{ $incident->cause ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <h2>Últimas checagens</h2>
    <div class="card table-wrap">
        @if ($checks->isEmpty())
            <div class="empty">Nenhuma checagem ainda.</div>
        @else
            <table>
                <thead><tr><th>Quando</th><th>Resultado</th><th>HTTP</th><th>Resposta</th><th>Erro</th></tr></thead>
                <tbody>
                    @foreach ($checks as $check)
                        <tr>
                            <td>{{ Format::dt($check->checked_at) }}</td>
                            <td class="{{ $check->is_successful ? 'ok' : 'fail' }}">{{ $check->is_successful ? 'OK' : 'Falhou' }}</td>
                            <td>{{ $check->status_code ?? '—' }}</td>
                            <td>{{ $check->response_time_ms !== null ? $check->response_time_ms.' ms' : '—' }}</td>
                            <td class="muted">{{ $check->error ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection