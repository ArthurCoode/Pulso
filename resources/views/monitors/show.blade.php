@extends('layouts.app')

@use('App\Support\Format')

@section('title', $monitor->name.' · Pulso')

@push('head')
    <meta http-equiv="refresh" content="30">
@endpush

@section('content')
    <div class="page-head">
        <div>
            <a class="back" href="{{ route('monitors.index') }}">Todos os monitores</a>
            <h1>{{ $monitor->name }}</h1>
            <div class="sub">
                @include('monitors._badge')
                <span class="muted">{{ $monitor->method }} {{ $monitor->url }}</span>
            </div>
            <div class="muted">
                Checa a cada {{ $monitor->interval_minutes }} {{ (int) $monitor->interval_minutes === 1 ? 'minuto' : 'minutos' }}
                e espera o status {{ $monitor->expected_status }}.
            </div>
        </div>

        <div class="actions">
            <form class="inline" method="POST" action="{{ route('monitors.toggle', $monitor) }}">
                @csrf
                <button class="btn" type="submit">{{ $monitor->is_active ? 'Pausar' : 'Retomar' }}</button>
            </form>
            <a class="btn" href="{{ route('monitors.edit', $monitor) }}">Editar</a>
            <form class="inline" method="POST" action="{{ route('monitors.destroy', $monitor) }}"
                  onsubmit="return confirm('Remover este monitor e todo o histórico dele?')">
                @csrf
                @method('DELETE')
                <button class="btn danger" type="submit">Remover</button>
            </form>
        </div>
    </div>

    @if ($monitor->openIncident)
        <div class="notice s-down">
            <strong>Incidente em andamento</strong> desde {{ Format::dt($monitor->openIncident->started_at) }}
            ({{ Format::incidentDuration($monitor->openIncident) }}).
            Motivo: {{ $monitor->openIncident->cause ?? 'não informado' }}
        </div>
    @endif

    <dl class="figures">
        <div>
            <dt>Uptime em 24 horas</dt>
            <dd>{{ Format::percent($uptime24) }}</dd>
        </div>
        <div>
            <dt>Uptime em 7 dias</dt>
            <dd>{{ Format::percent($uptime7) }}</dd>
        </div>
        <div>
            <dt>Uptime em 30 dias</dt>
            <dd>{{ Format::percent($uptime30) }}</dd>
        </div>
        <div>
            <dt>Resposta média em 24 horas</dt>
            <dd>{{ $avgResponse !== null ? $avgResponse.' ms' : '—' }}</dd>
        </div>
    </dl>

    <h2>Tempo de resposta</h2>
    <div class="card trace">
        @php
            $values = $series->pluck('response_time_ms')->map(fn ($v) => (int) $v)->all();
            $count = count($values);
        @endphp

        @if ($count > 1)
            @php
                $width = 600;
                $height = 120;
                $pad = 10;
                $max = max($values) ?: 1;
                $points = [];
                foreach ($values as $i => $value) {
                    $x = round($i / ($count - 1) * $width, 1);
                    $y = round($height - $pad - ($value / $max) * ($height - 2 * $pad), 1);
                    $points[] = $x.','.$y;
                }
                $line = implode(' ', $points);
                $area = $line.' '.$width.','.$height.' 0,'.$height;
            @endphp
            <div class="trace-plot">
                <svg class="spark" viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" role="img"
                     aria-label="Tempo de resposta das últimas {{ $count }} checagens bem-sucedidas">
                    <polygon points="{{ $area }}" fill="currentColor" fill-opacity="0.12" />
                    <polyline points="{{ $line }}" fill="none" stroke="currentColor" stroke-width="2"
                              stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                </svg>
            </div>
            <p class="muted">Últimas {{ $count }} checagens bem-sucedidas. O ponto mais alto é {{ $max }} ms.</p>
        @else
            <p class="muted">O gráfico aparece depois de duas checagens bem-sucedidas.</p>
        @endif
    </div>

    <h2>Incidentes</h2>
    <div class="card table-wrap">
        @if ($incidents->isEmpty())
            <div class="empty">Nenhum incidente registrado. Quando este monitor cair, o período aparece aqui.</div>
        @else
            <table>
                <thead>
                    <tr><th>Início</th><th>Fim</th><th>Duração</th><th>Motivo</th></tr>
                </thead>
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
            <div class="empty">Nenhuma checagem ainda. A primeira acontece em até 1 minuto.</div>
        @else
            <table>
                <thead>
                    <tr><th>Quando</th><th>Resultado</th><th>HTTP</th><th>Resposta</th><th>Erro</th></tr>
                </thead>
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
