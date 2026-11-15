@extends('layouts.app')
@section('title', 'Editar usuario')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-header">
        <h1>Editar usuario</h1>
        <p>{{ $usuario->correo }} · código #{{ $usuario->id }}</p>
    </div>

    <div class="admin-card">
        <div class="admin-card-title">Datos de la cuenta</div>
        <div class="admin-card-body">
            <form action="{{ route('admin.usuarios.update', $usuario->id) }}" method="POST">
                @csrf
                @method('PUT')
                @include('admin.usuarios._form')

                <div class="mt-3">
                    <button class="btn btn-brand">Actualizar</button>
                    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
