@extends('layouts.app')
@section('title', 'Centros de Salud')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-hero mb-4">
        <h1>Centros de Salud</h1>
        <p>Hospitales, clínicas y laboratorios donde atienden los médicos.</p>
    </div>

    @if (session('ok'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('ok') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="admin-toolbar">
        <div class="d-flex gap-2">
            <a class="btn btn-success btn-sm" href="{{ route('admin.centros_salud.create') }}">
                Agregar nuevo centro
            </a>
            <a class="btn btn-outline-danger btn-sm" target="_blank"
               href="{{ route('admin.centros_salud.reporte', ['q' => $q, 'tipo' => $tipo]) }}">
                Descargar reporte PDF
            </a>
        </div>

        <form action="{{ route('admin.centros_salud.index') }}" method="GET" class="admin-search">
            <select name="tipo" class="form-select form-select-sm" style="width: auto">
                <option value="">Todos los tipos</option>
                @foreach ($tipos as $valor => $etiqueta)
                    <option value="{{ $valor }}" {{ $tipo === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                @endforeach
            </select>
            <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm"
                   placeholder="Buscar por nombre, dirección o teléfono">
            <button class="btn btn-admin btn-sm">Buscar</button>
            @if ($q !== '' || $tipo !== '')
                <a href="{{ route('admin.centros_salud.index') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-bordered table-admin align-middle">
            <thead>
                <tr>
                    <th style="width: 70px">ID</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                    <th class="text-center">Ubicación</th>
                    <th class="text-center">Médicos</th>
                    <th style="width: 200px">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($centros as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->nombre }}</td>
                        <td>
                            @php
                                $color = ['hospital' => 'bg-primary', 'clinica' => 'bg-success', 'laboratorio' => 'bg-secondary'][$item->tipo] ?? 'bg-dark';
                            @endphp
                            <span class="badge {{ $color }}">{{ $tipos[$item->tipo] ?? $item->tipo }}</span>
                        </td>
                        <td>{{ \Illuminate\Support\Str::limit($item->direccion, 50) }}</td>
                        <td>{{ $item->telefono }}</td>
                        <td class="text-center">
                            <a href="https://www.google.com/maps?q={{ $item->latitud }},{{ $item->longitud }}"
                               target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
                                Ver mapa
                            </a>
                        </td>
                        <td class="text-center"><strong>{{ $item->medicos_count }}</strong></td>
                        <td>
                            <a class="btn btn-warning btn-sm"
                               href="{{ route('admin.centros_salud.edit', $item->id) }}">
                                Modificar
                            </a>

                            <button
                                type="button"
                                class="btn btn-danger btn-sm"
                                data-url="{{ route('admin.centros_salud.destroy', $item->id) }}"
                                data-token="{{ csrf_token() }}"
                                data-nombre="{{ $item->nombre }}"
                                onclick="destroy(this)">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            {{ ($q !== '' || $tipo !== '') ? 'No se encontraron centros de salud con esos filtros.' : 'Aún no hay centros de salud registrados.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted">
            Mostrando {{ $centros->count() }} de {{ $centros->total() }} centro(s) de salud
        </small>
        {{ $centros->links() }}
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/centro_salud.js') }}"></script>
@endsection
