@extends('layouts.app')
@section('title', 'Crear Especialidad')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-hero mb-4">
        <h1>Crear Especialidad</h1>
        <p>Vamos a registrar una nueva especialidad médica en el catálogo.</p>
    </div>

    <div class="admin-card">
        <form action="{{ route('admin.especialidades.store') }}" method="POST" novalidate>
            @csrf

            @include('admin.especialidades._form')

            <div class="mt-4">
                <button class="btn btn-admin">Guardar</button>
                <a href="{{ route('admin.especialidades.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
