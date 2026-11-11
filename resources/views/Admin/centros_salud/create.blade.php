@extends('layouts.app')
@section('title', 'Crear Centro de Salud')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-hero mb-4">
        <h1>Crear Centro de Salud</h1>
        <p>Vamos a registrar un nuevo hospital, clínica o laboratorio.</p>
    </div>

    <div class="admin-card">
        <form action="{{ route('admin.centros_salud.store') }}" method="POST" novalidate>
            @csrf

            @include('admin.centros_salud._form')

            <div class="mt-4">
                <button class="btn btn-admin">Guardar</button>
                <a href="{{ route('admin.centros_salud.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
