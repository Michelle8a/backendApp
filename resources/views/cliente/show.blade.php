@extends('layouts.app')
@section('title', 'Clientes')
@section('content')
    <div class="hero mb-4">
        <h1 class="fw-bold text-primary mb-1">Clientes</h1>
        <p class="mb-0">Listado de clientes.</p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a class="btn btn-success btn-sm" href="{{ route('cliente.create') }}">
            Agregar nuevo cliente
        </a>

        <!-- Aqui vA EL BOTON pdf-->
    </div>

    <table class="table table-hover table-bordered mt-2">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Fecha de nacimiento</th>
                <th>Pedidos</th>
                @if (auth()->user()->is_admin)
                    <th>Acciones</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($clientes as $item)
                <tr>
                    <td>{{ $item->Id_Cliente }}</td>
                    <td>{{ $item->Nombre }}</td>
                    <td>{{ $item->Apellido }}</td>
                    <td>{{ $item->Fecha_Nac->format('d/m/Y') }}</td>
                    <td>{{ $item->pedidos_count }}</td>

                    <td>
                        <a class="btn btn-warning btn-sm"
                            href="{{ route('cliente.edit', $item->Id_Cliente) }}">
                            Modificar
                        </a>

                        <button
                            type="button"
                            class="btn btn-danger btn-sm"
                            data-url="{{ route('cliente.destroy', $item->Id_Cliente) }}"
                            data-token="{{ csrf_token() }}"
                            data-pedidos="{{ $item->pedidos_count }}"
                            onclick="destroy(this)">
                            Eliminar
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No hay clientes</td>
                </tr>
            @endforelse
        </tbody>
    </table>

@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/cliente.js') }}"></script>
@endsection
