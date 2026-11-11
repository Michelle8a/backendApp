@extends('layouts.app')
@section('title', 'Actualizar Farmacia')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-hero mb-4">
        <h1>Actualizar Farmacia</h1>
        <p>Vamos a actualizar la farmacia seleccionada.</p>
    </div>

    <div class="admin-card">
        <form action="{{ route('admin.farmacias.update', $farmacia->id) }}" method="POST" novalidate>
            @csrf
            @method('PUT')

            @include('admin.farmacias._form')

            <div class="mt-4">
                <button class="btn btn-admin">Actualizar</button>
                <a href="{{ route('admin.farmacias.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
