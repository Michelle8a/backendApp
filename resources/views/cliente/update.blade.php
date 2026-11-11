@extends('layouts.app')
@section('title', 'Modificar Cliente')
@section('content')
    <div class="hero">
        <h1 class="fw-bold text-primary">Modificar Cliente</h1>
        <p class="mb-0">Vamos a modificar los datos de un cliente existente.</p>

        <div class="container">
            <form action="{{ route('cliente.update', $cliente->Id_Cliente) }}" method="POST">
            @csrf
            @method('PUT')
                <div class="row">
                    <div class="col-6">
                        Nombre
                        <input type="text" class="form-control" name="Nombre" maxlength="50"
                            value="{{ old('Nombre', $cliente->Nombre) }}">
                        @error('Nombre')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-6">
                        Apellido
                        <input type="text" class="form-control" name="Apellido" maxlength="50"
        value="{{ old('Apellido', $cliente->Apellido) }}">
                        @error('Apellido')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-12">
                        Fecha de nacimiento
                        <input type="date" class="form-control" name="Fecha_Nac" value="{{ old('Fecha_Nac', $cliente->Fecha_Nac->format('Y-m-d')) }}">
                        @error('Fecha_Nac')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-12 mt-2">
                        <button class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection