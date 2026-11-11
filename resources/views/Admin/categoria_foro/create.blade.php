@extends('layouts.app')
@section('title', 'Crear categoría')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-header">
        <h1>Crear categoría</h1>
        <p>Registra una nueva categoría para el foro.</p>
    </div>

    <div class="admin-card">
        <form action="{{ route('categoria_foro.store') }}" method="POST" class="admin-form" novalidate>
            @csrf
            <div class="row">
                <div class="col-12 col-md-8 mb-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" id="nombre" name="nombre" maxlength="100"
                           class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre') }}" placeholder="Ej. Nutrición" autofocus>
                    @error('nombre')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-12 mt-2">
                    <button class="btn btn-admin">Guardar</button>
                    <a href="{{ route('categoria_foro.show') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
@endsection
