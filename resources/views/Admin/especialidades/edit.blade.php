@extends('layouts.app')
@section('title', 'Actualizar Especialidad')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-hero mb-4">
        <h1>Actualizar Especialidad</h1>
        <p>Vamos a actualizar la especialidad seleccionada.</p>
    </div>

    <div class="admin-card">
        <form action="{{ route('admin.especialidades.update', $especialidad->id) }}" method="POST" novalidate>
            @csrf
            @method('PUT')

            @include('admin.especialidades._form')

            <div class="mt-4">
                <button class="btn btn-admin">Actualizar</button>
                <a href="{{ route('admin.especialidades.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
