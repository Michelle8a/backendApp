@extends('layouts.app')
@section('title', 'Especialidades')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-hero mb-4">
        <h1>Especialidades</h1>
        <p>Catálogo de especialidades médicas que se asignan a los médicos.</p>
    </div>

    @if (session('ok'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('ok') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="admin-toolbar">
        <div class="d-flex gap-2">
            <a class="btn btn-success btn-sm" href="{{ route('admin.especialidades.create') }}">
                Agregar nueva especialidad
            </a>
            <a class="btn btn-outline-danger btn-sm" target="_blank"
               href="{{ route('admin.especialidades.reporte', ['q' => $q]) }}">
                Descargar reporte PDF
            </a>
        </div>

        <form action="{{ route('admin.especialidades.index') }}" method="GET" class="admin-search">
            <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm"
                   placeholder="Buscar por nombre o descripción">
            <button class="btn btn-admin btn-sm">Buscar</button>
            @if ($q !== '')
                <a href="{{ route('admin.especialidades.index') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-bordered table-admin align-middle">
            <thead>
                <tr>
                    <th style="width: 80px">ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th class="text-center">Médicos</th>
                    <th class="text-center">Verificados</th>
                    <th style="width: 200px">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($especialidades as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->nombre }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($item->descripcion, 70) ?: '—' }}</td>
                        <td class="text-center"><strong>{{ $item->medicos_count }}</strong></td>
                        <td class="text-center">{{ $item->verificados_count }}</td>
                        <td>
                            <a class="btn btn-warning btn-sm"
                               href="{{ route('admin.especialidades.edit', $item->id) }}">
                                Modificar
                            </a>

                            <button
                                type="button"
                                class="btn btn-danger btn-sm"
                                data-url="{{ route('admin.especialidades.destroy', $item->id) }}"
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
                            {{ $q !== '' ? 'No se encontraron especialidades para "' . $q . '".' : 'Aún no hay especialidades registradas.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted">
            Mostrando {{ $especialidades->count() }} de {{ $especialidades->total() }} especialidad(es)
        </small>
        {{ $especialidades->links() }}
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/especialidad.js') }}"></script>
@endsection
