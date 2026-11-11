@extends('layouts.app')
@section('title', 'Crear Pedido')
@section('content')
    <div class="hero">
        <h1 class="fw-bold text-primary">Crear Pedido</h1>
        <p class="mb-0">Vamos a registrar un nuevo pedido.</p>

        <div class="container">
            <form action="{{ route('pedido.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-6">
                        Fecha del pedido
                        <input type="datetime-local" class="form-control" name="FechaPedido"
                               value="{{ old('FechaPedido') }}">
                        @error('FechaPedido')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-6">
                        Fecha de entrega
                        <input type="datetime-local" class="form-control" name="FechaEntrega"
                               value="{{ old('FechaEntrega') }}">
                        @error('FechaEntrega')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-12">
                        Cliente
                        <select name="Id_Cliente" class="form-control">
                            <option value="">-- Seleccione un cliente --</option>
                            @foreach ($clientes as $item)
                                <option value="{{ $item->Id_Cliente }}"
                                    {{ old('Id_Cliente') == $item->Id_Cliente ? 'selected' : '' }}>
                                    {{ $item->Nombre }} {{ $item->Apellido }}
                                </option>
                            @endforeach
                        </select>
                        @error('Id_Cliente')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-12">
                        Observaciones
                        <textarea name="Observaciones" class="form-control" rows="3" maxlength="150">{{ old('Observaciones') }}</textarea>
                        @error('Observaciones')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-12 mt-2">
                        <button class="btn btn-primary">Guardar</button>
                        <a href="{{ route('pedido.show') }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection