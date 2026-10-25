@extends('layouts.app')

@section('title', 'Novo monitor · Pulso')

@section('content')
    <div class="head"><h1>Novo monitor</h1></div>

    <div class="card">
        <form class="monitor-form" method="POST" action="{{ route('monitors.store') }}">
            @csrf
            @include('monitors._form')
            <button class="btn primary" type="submit">Criar monitor</button>
            <a class="btn" href="{{ route('monitors.index') }}">Cancelar</a>
        </form>
    </div>
@endsection