@extends('layouts.app')
@section('title', 'Crear Alergia')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-hero mb-4">
        <h1>Crear Alergia</h1>
        <p>Vamos a registrar una nueva alergia en el catálogo.</p>
    </div>

    <div class="admin-card">
        <form action="{{ route('admin.alergias.store') }}" method="POST" novalidate>
            @csrf

            @include('admin.alergias._form')

            <div class="mt-4">
                <button class="btn btn-admin">Guardar</button>
                <a href="{{ route('admin.alergias.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
