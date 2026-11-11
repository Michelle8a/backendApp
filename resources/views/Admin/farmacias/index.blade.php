@extends('layouts.app')
@section('title', 'Farmacias')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-hero mb-4">
        <h1>Farmacias</h1>
        <p>Farmacias con las que los pacientes pueden compartir sus recetas.</p>
    </div>

    @if (session('ok'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('ok') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="admin-toolbar">
        <div class="d-flex gap-2">
            <a class="btn btn-success btn-sm" href="{{ route('admin.farmacias.create') }}">
                Agregar nueva farmacia
            </a>
            <a class="btn btn-outline-danger btn-sm" target="_blank"
               href="{{ route('admin.farmacias.reporte', ['q' => $q]) }}">
                Descargar reporte PDF
            </a>
        </div>

        <form action="{{ route('admin.farmacias.index') }}" method="GET" class="admin-search">
            <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm"
                   placeholder="Buscar por nombre, dirección, teléfono o correo">
            <button class="btn btn-admin btn-sm">Buscar</button>
            @if ($q !== '')
                <a href="{{ route('admin.farmacias.index') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-bordered table-admin align-middle">
            <thead>
                <tr>
                    <th style="width: 70px">ID</th>
                    <th>Nombre</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th class="text-center">Ubicación</th>
                    <th class="text-center">Recetas</th>
                    <th class="text-center">Vigentes</th>
                    <th style="width: 200px">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($farmacias as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->nombre }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($item->direccion, 50) }}</td>
                        <td>{{ $item->telefono }}</td>
                        <td><a href="mailto:{{ $item->correo }}">{{ $item->correo }}</a></td>
                        <td class="text-center">
                            <a href="https://www.google.com/maps?q={{ $item->latitud }},{{ $item->longitud }}"
                               target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
                                Ver mapa
                            </a>
                        </td>
                        <td class="text-center"><strong>{{ $item->recetas_count }}</strong></td>
                        <td class="text-center">{{ $item->vigentes_count }}</td>
                        <td>
                            <a class="btn btn-warning btn-sm"
                               href="{{ route('admin.farmacias.edit', $item->id) }}">
                                Modificar
                            </a>

                            <button
                                type="button"
                                class="btn btn-danger btn-sm"
                                data-url="{{ route('admin.farmacias.destroy', $item->id) }}"
                                data-token="{{ csrf_token() }}"
                                data-nombre="{{ $item->nombre }}"
                                onclick="destroy(this)">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            {{ $q !== '' ? 'No se encontraron farmacias para "' . $q . '".' : 'Aún no hay farmacias registradas.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted">
            Mostrando {{ $farmacias->count() }} de {{ $farmacias->total() }} farmacia(s)
        </small>
        {{ $farmacias->links() }}
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/farmacia.js') }}"></script>
@endsection
