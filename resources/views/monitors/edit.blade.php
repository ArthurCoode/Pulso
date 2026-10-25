@extends('layouts.app')

@section('title', 'Editar '.$monitor->name.' · Pulso')

@section('content')
    <div class="page-head">
        <div>
            <a class="back" href="{{ route('monitors.show', $monitor) }}">Voltar para {{ $monitor->name }}</a>
            <h1>Editar monitor</h1>
            <div class="muted">Se você mudar a URL, o histórico de estado recomeça e um incidente aberto é encerrado.</div>
        </div>
    </div>

    <div class="card">
        <form class="monitor-form" method="POST" action="{{ route('monitors.update', $monitor) }}">
            @csrf
            @method('PUT')
            @include('monitors._form')
            <button class="btn primary" type="submit">Salvar alterações</button>
            <a class="btn" href="{{ route('monitors.show', $monitor) }}">Cancelar</a>
        </form>
    </div>
@endsection
