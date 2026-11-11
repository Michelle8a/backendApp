@extends('layouts.app')
@section('title', 'Crear Cliente')
@section('content')
    <div class="hero">
        <h1 class="fw-bold text-primary">Crear Cliente</h1>
        <p class="mb-0">Vamos a registrar un nuevo cliente.</p>

        <div class="container">
            <form action="{{ route('cliente.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-6">
                        Nombre
                        <input type="text" class="form-control" name="Nombre" maxlength="50"
                               value="{{ old('Nombre') }}">
                        @error('Nombre')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-6">
                        Apellido
                        <input type="text" class="form-control" name="Apellido" maxlength="50"
                               value="{{ old('Apellido') }}">
                        @error('Apellido')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-12">
                        Fecha de nacimiento
                        <input type="date" class="form-control" name="Fecha_Nac"
                               value="{{ old('Fecha_Nac') }}">
                        @error('Fecha_Nac')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-12 mt-2">
                        <button class="btn btn-primary">Guardar</button>
                        <a href="{{ route('cliente.show') }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection