@extends('layouts.app')
@section('title', 'Actualizar Centro de Salud')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-hero mb-4">
        <h1>Actualizar Centro de Salud</h1>
        <p>Vamos a actualizar el centro de salud seleccionado.</p>
    </div>

    <div class="admin-card">
        <form action="{{ route('admin.centros_salud.update', $centro->id) }}" method="POST" novalidate>
            @csrf
            @method('PUT')

            @include('admin.centros_salud._form')

            <div class="mt-4">
                <button class="btn btn-admin">Actualizar</button>
                <a href="{{ route('admin.centros_salud.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
