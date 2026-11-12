@extends('layouts.app')
@section('title', 'Actualizar categoría')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-header">
        <h1>Actualizar categoría</h1>
        <p>
            Editando "{{ $categoria->nombre }}"
            · {{ $categoria->publicaciones_count }}
            {{ $categoria->publicaciones_count === 1 ? 'publicación asociada' : 'publicaciones asociadas' }}.
        </p>
    </div>

    <div class="admin-card">
        <form action="{{ route('categoria_foro.update', $categoria->id) }}" method="POST" class="admin-form" novalidate>
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-12 col-md-8 mb-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" id="nombre" name="nombre" maxlength="100"
                           class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre', $categoria->nombre) }}" autofocus>
                    @error('nombre')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-12 mt-2">
                    <button class="btn btn-admin">Actualizar</button>
                    <a href="{{ route('categoria_foro.show') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
@endsection
