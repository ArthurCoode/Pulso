@extends('layouts.app')

@section('title', 'Novo monitor · Pulso')

@section('content')
    <div class="page-head">
        <div>
            <a class="back" href="{{ route('monitors.index') }}">Todos os monitores</a>
            <h1>Novo monitor</h1>
            <div class="muted">A primeira checagem acontece em até 1 minuto depois de salvar.</div>
        </div>
    </div>

    <div class="card">
        <form class="monitor-form" method="POST" action="{{ route('monitors.store') }}">
            @csrf
            @include('monitors._form')
            <button class="btn primary" type="submit">Criar monitor</button>
            <a class="btn" href="{{ route('monitors.index') }}">Cancelar</a>
        </form>
    </div>
@endsection
