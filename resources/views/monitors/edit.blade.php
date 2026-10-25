@extends('layouts.app')

@section('title', 'Editar '.$monitor->name.' · Pulso')

@section('content')
    <div class="head"><h1>Editar monitor</h1></div>

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