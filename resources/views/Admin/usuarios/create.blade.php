@extends('layouts.app')
@section('title', 'Nuevo usuario')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-header">
        <h1>Nuevo usuario</h1>
        <p>Registra una cuenta y asígnale su rol.</p>
    </div>

    <div class="admin-card">
        <div class="admin-card-title">Datos de la cuenta</div>
        <div class="admin-card-body">
            <form action="{{ route('admin.usuarios.store') }}" method="POST">
                @csrf
                @include('admin.usuarios._form', ['usuario' => null])

                <div class="alert alert-info mt-3 mb-0">
                    El consentimiento de uso queda como <strong>pendiente</strong>: lo debe aceptar la propia
                    persona cuando inicie sesión. Una cuenta de médico o paciente no crea su perfil aquí;
                    el paciente lo completa en la app y los médicos se registran desde el módulo de Médicos.
                </div>

                <div class="mt-3">
                    <button class="btn btn-brand">Guardar</button>
                    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
