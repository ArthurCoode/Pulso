@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Monitores · Pulso')

@push('head')
    {{-- Atualiza a página sozinha a cada 30 s, sem JavaScript. --}}
    <meta http-equiv="refresh" content="30">
@endpush

@section('content')
    @php
        $total = $summary['total'];
        $up = $summary['up'];
        $down = $summary['down'];
        $other = $summary['other'];

        if ($total === 0) {
            $heroClass = 's-unknown';
            $heroTitle = 'Nenhum monitor ainda';
        } elseif ($down > 0) {
            $heroClass = 's-down';
            $heroTitle = $down === 1 ? '1 serviço fora do ar' : "{$down} serviços fora do ar";
        } elseif ($up > 0) {
            $heroClass = 's-up';
            $heroTitle = $other === 0 ? 'Tudo operacional' : 'Nenhum problema detectado';
        } else {
            $heroClass = 's-unknown';
            $heroTitle = 'Aguardando as primeiras checagens';
        }

        $parts = [];
        if ($up > 0) {
            $parts[] = $up === 1 ? '1 operacional' : "{$up} operacionais";
        }
        if ($down > 0) {
            $parts[] = "{$down} fora do ar";
        }
        if ($other > 0) {
            $parts[] = $other === 1 ? '1 pausado ou aguardando' : "{$other} pausados ou aguardando";
        }
        $legend = implode(', ', $parts);
    @endphp

    <section class="hero {{ $heroClass }}">
        <h1><span class="beat" aria-hidden="true"></span>{{ $heroTitle }}</h1>

        @if ($total === 0)
            <p>Cadastre um site ou uma API e o Pulso passa a checar a disponibilidade e avisar quando algo cair.</p>
            <p><a class="btn primary" href="{{ route('monitors.create') }}">Cadastrar o primeiro monitor</a></p>
        @else
            <div class="healthbar" role="img" aria-label="{{ $legend }}">
                @if ($up > 0)<span class="up" style="flex: {{ $up }}"></span>@endif
                @if ($down > 0)<span class="down" style="flex: {{ $down }}"></span>@endif
                @if ($other > 0)<span class="idle" style="flex: {{ $other }}"></span>@endif
            </div>
            <p>{{ $legend }}. A página atualiza sozinha a cada 30 segundos.</p>
        @endif
    </section>

    @if ($monitors->isNotEmpty())
        <ul class="ledger">
            <li class="ledger-head" aria-hidden="true">
                <span class="cell-state">Estado</span>
                <span class="cell-main">Monitor</span>
                <span class="cell-uptime">Uptime 24h</span>
                <span class="cell-resp">Resposta</span>
                <span class="cell-pulse">Últimas checagens</span>
                <span class="cell-act"></span>
            </li>

            @foreach ($monitors as $monitor)
                @php
                    $stateClass = ! $monitor->is_active ? 's-paused' : 's-'.$monitor->status->value;
                    $monitorTicks = $ticks[$monitor->id] ?? [];
                    $lastMs = $monitor->latestCheck?->response_time_ms;
                @endphp
                <li class="mon {{ $stateClass }}">
                    <div class="cell-state">@include('monitors._badge')</div>

                    <div class="cell-main">
                        <a class="mon-name" href="{{ route('monitors.show', $monitor) }}">{{ $monitor->name }}</a>
                        <span class="mon-url">{{ $monitor->url }}</span>
                    </div>

                    <div class="cell-uptime">
                        <span class="cell-label">Uptime 24h</span>
                        <span class="figure">{{ Format::percent($uptime[$monitor->id] ?? null) }}</span>
                    </div>

                    <div class="cell-resp">
                        <span class="cell-label">Resposta</span>
                        <span class="figure">{{ $lastMs !== null ? $lastMs.' ms' : '—' }}</span>
                    </div>

                    <div class="cell-pulse">
                        <span class="cell-label">Últimas checagens</span>
                        @if ($monitorTicks === [])
                            <span class="pulse-empty">Sem dados</span>
                        @else
                            <div class="pulse" role="img" aria-label="Últimas {{ count($monitorTicks) }} checagens">
                                @foreach ($monitorTicks as $tick)
                                    <i class="tick {{ $tick['ok'] ? '' : 'fail' }}" style="height: {{ $tick['height'] }}%"></i>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="cell-act">
                        <form class="inline" method="POST" action="{{ route('monitors.toggle', $monitor) }}">
                            @csrf
                            <button class="btn small" type="submit">{{ $monitor->is_active ? 'Pausar' : 'Retomar' }}</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
