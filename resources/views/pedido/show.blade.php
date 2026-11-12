@extends('layouts.app')
@section('title', 'Pedidos')
@section('content')
    <div class="hero mb-4">
        <h1 class="fw-bold text-primary mb-1">Pedidos</h1>
        <p class="mb-0">Listado de pedidos.</p>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a class="btn btn-success btn-sm" href="{{ route('pedido.create') }}">
            Agregar nuevo pedido
        </a>

<!-- Aqui vA EL BOTON pdf-->
    </div>

        <table class="table table-hover table-bordered mt-2">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Fecha de pedido</th>
                    <th>Fecha de entrega</th>
                    <th>Observaciones</th>
                    <th>Cliente</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                @foreach($pedidos as $item)
                    <tr>
                        <td>{{ $item->Id_Pedido }}</td>
                        <td>{{ $item->FechaPedido }}</td>
                        <td>{{ $item->FechaEntrega }}</td>
                        <td>{{ $item->Observaciones }}</td>
                        <td>{{ $item->cliente->Nombre ?? 'Sin cliente' }}</td>

                        <td>
                            <a class="btn btn-warning btn-sm"
                            href="{{ route('pedido.edit', $item->Id_Pedido) }}">
                                Modificar
                            </a>

                            <button
                                type="button"
                                class="btn btn-danger btn-sm"
                                data-url="{{ route('pedido.destroy', $item->Id_Pedido) }}"
                                data-token="{{ csrf_token() }}"
                                onclick="destroy(this)">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/pedido.js') }}"></script>
@endsection