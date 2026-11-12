@extends('layouts.app')
@section('title', 'Actualizar Alergia')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-hero mb-4">
        <h1>Actualizar Alergia</h1>
        <p>Vamos a actualizar la alergia seleccionada.</p>
    </div>

    <div class="admin-card">
        <form action="{{ route('admin.alergias.update', $alergia->id) }}" method="POST" novalidate>
            @csrf
            @method('PUT')

            @include('admin.alergias._form')

            <div class="mt-4">
                <button class="btn btn-admin">Actualizar</button>
                <a href="{{ route('admin.alergias.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
