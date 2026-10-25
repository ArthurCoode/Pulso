@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Monitores · Pulso')

@push('head')
    {{-- Atualiza a página sozinha a cada 30 s, sem JavaScript. --}}
    <meta http-equiv="refresh" content="30">
@endpush

@section('content')
    <div class="head">
        <h1>Monitores</h1>
        <a class="btn primary" href="{{ route('monitors.create') }}">+ Novo monitor</a>
    </div>

    <div class="summary">
        <div class="card"><div class="num">{{ $summary['total'] }}</div><div class="muted">Total</div></div>
        <div class="card"><div class="num ok">{{ $summary['up'] }}</div><div class="muted">Operacionais</div></div>
        <div class="card"><div class="num fail">{{ $summary['down'] }}</div><div class="muted">Fora do ar</div></div>
        <div class="card"><div class="num">{{ $summary['other'] }}</div><div class="muted">Pausados / aguardando</div></div>
    </div>

    @if ($monitors->isEmpty())
        <div class="card empty">Nenhum monitor ainda. Cadastre o primeiro em “Novo monitor”.</div>
    @else
        <div class="card table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Monitor</th>
                        <th>Uptime 24h</th>
                        <th>Resposta</th>
                        <th>Última checagem</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($monitors as $monitor)
                        <tr>
                            <td>@include('monitors._badge')</td>
                            <td>
                                <a href="{{ route('monitors.show', $monitor) }}"><strong>{{ $monitor->name }}</strong></a><br>
                                <span class="muted">{{ $monitor->url }}</span>
                            </td>
                            <td>{{ Format::percent($uptime[$monitor->id] ?? null) }}</td>
                            <td>{{ $monitor->latestCheck?->response_time_ms ? $monitor->latestCheck->response_time_ms.' ms' : '—' }}</td>
                            <td>{{ Format::dt($monitor->last_checked_at, 'd/m H:i:s') }}</td>
                            <td class="actions">
                                <form method="POST" action="{{ route('monitors.toggle', $monitor) }}">
                                    @csrf
                                    <button class="btn small" type="submit">{{ $monitor->is_active ? 'Pausar' : 'Retomar' }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection