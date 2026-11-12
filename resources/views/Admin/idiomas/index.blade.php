@extends('layouts.app')
@section('title', 'Idiomas')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-hero mb-4">
        <h1>Idiomas</h1>
        <p>Catálogo de idiomas disponibles para pacientes y médicos.</p>
    </div>

    @if (session('ok'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('ok') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="admin-toolbar">
        <div class="d-flex gap-2">
            <a class="btn btn-success btn-sm" href="{{ route('admin.idiomas.create') }}">
                Agregar nuevo idioma
            </a>
            <a class="btn btn-outline-danger btn-sm" target="_blank"
               href="{{ route('admin.idiomas.reporte', ['q' => $q]) }}">
                Descargar reporte PDF
            </a>
        </div>

        <form action="{{ route('admin.idiomas.index') }}" method="GET" class="admin-search">
            <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm"
                   placeholder="Buscar por nombre o código">
            <button class="btn btn-admin btn-sm">Buscar</button>
            @if ($q !== '')
                <a href="{{ route('admin.idiomas.index') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-bordered table-admin align-middle">
            <thead>
                <tr>
                    <th style="width: 80px">ID</th>
                    <th>Nombre</th>
                    <th class="text-center">Código</th>
                    <th class="text-center">Pacientes</th>
                    <th class="text-center">Médicos</th>
                    <th style="width: 200px">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($idiomas as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->nombre }}</td>
                        <td class="text-center"><span class="badge badge-codigo">{{ $item->codigo }}</span></td>
                        <td class="text-center">{{ $item->pacientes_count }}</td>
                        <td class="text-center">{{ $item->medicos_count }}</td>
                        <td>
                            <a class="btn btn-warning btn-sm"
                               href="{{ route('admin.idiomas.edit', $item->id) }}">
                                Modificar
                            </a>

                            <button
                                type="button"
                                class="btn btn-danger btn-sm"
                                data-url="{{ route('admin.idiomas.destroy', $item->id) }}"
                                data-token="{{ csrf_token() }}"
                                data-nombre="{{ $item->nombre }}"
                                onclick="destroy(this)">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            {{ $q !== '' ? 'No se encontraron idiomas para "' . $q . '".' : 'Aún no hay idiomas registrados.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted">
            Mostrando {{ $idiomas->count() }} de {{ $idiomas->total() }} idioma(s)
        </small>
        {{ $idiomas->links() }}
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/idioma.js') }}"></script>
@endsection
