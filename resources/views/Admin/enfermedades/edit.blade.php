@extends('layouts.app')
@section('title', 'Actualizar Enfermedad')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-hero mb-4">
        <h1>Actualizar Enfermedad</h1>
        <p>Vamos a actualizar la enfermedad seleccionada.</p>
    </div>

    <div class="admin-card">
        <form action="{{ route('admin.enfermedades.update', $enfermedad->id) }}" method="POST" novalidate>
            @csrf
            @method('PUT')

            @include('admin.enfermedades._form')

            <div class="mt-4">
                <button class="btn btn-admin">Actualizar</button>
                <a href="{{ route('admin.enfermedades.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
