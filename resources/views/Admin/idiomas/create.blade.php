@extends('layouts.app')
@section('title', 'Crear Idioma')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-hero mb-4">
        <h1>Crear Idioma</h1>
        <p>Vamos a registrar un nuevo idioma en el catálogo.</p>
    </div>

    <div class="admin-card">
        <form action="{{ route('admin.idiomas.store') }}" method="POST" novalidate>
            @csrf

            @include('admin.idiomas._form')

            <div class="mt-4">
                <button class="btn btn-admin">Guardar</button>
                <a href="{{ route('admin.idiomas.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
