@extends('layouts.app')
@section('title', 'Crear Enfermedad')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-hero mb-4">
        <h1>Crear Enfermedad</h1>
        <p>Vamos a registrar una nueva enfermedad en el catálogo.</p>
    </div>

    <div class="admin-card">
        <form action="{{ route('admin.enfermedades.store') }}" method="POST" novalidate>
            @csrf

            @include('admin.enfermedades._form')

            <div class="mt-4">
                <button class="btn btn-admin">Guardar</button>
                <a href="{{ route('admin.enfermedades.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
