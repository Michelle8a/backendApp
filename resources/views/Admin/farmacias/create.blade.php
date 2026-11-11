@extends('layouts.app')
@section('title', 'Crear Farmacia')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-hero mb-4">
        <h1>Crear Farmacia</h1>
        <p>Vamos a registrar una nueva farmacia para compartir recetas.</p>
    </div>

    <div class="admin-card">
        <form action="{{ route('admin.farmacias.store') }}" method="POST" novalidate>
            @csrf

            @include('admin.farmacias._form')

            <div class="mt-4">
                <button class="btn btn-admin">Guardar</button>
                <a href="{{ route('admin.farmacias.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
