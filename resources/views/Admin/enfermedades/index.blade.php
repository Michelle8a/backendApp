@extends('layouts.app')
@section('title', 'Enfermedades')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-hero mb-4">
        <h1>Enfermedades</h1>
        <p>Catálogo de enfermedades que los pacientes pueden registrar en su perfil.</p>
    </div>

    @if (session('ok'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('ok') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="admin-toolbar">
        <div class="d-flex gap-2">
            <a class="btn btn-success btn-sm" href="{{ route('admin.enfermedades.create') }}">
                Agregar nueva enfermedad
            </a>
            <a class="btn btn-outline-danger btn-sm" target="_blank"
               href="{{ route('admin.enfermedades.reporte', ['q' => $q]) }}">
                Descargar reporte PDF
            </a>
        </div>

        <form action="{{ route('admin.enfermedades.index') }}" method="GET" class="admin-search">
            <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm"
                   placeholder="Buscar por nombre">
            <button class="btn btn-admin btn-sm">Buscar</button>
            @if ($q !== '')
                <a href="{{ route('admin.enfermedades.index') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-bordered table-admin align-middle">
            <thead>
                <tr>
                    <th style="width: 80px">ID</th>
                    <th>Nombre</th>
                    <th class="text-center">Pacientes</th>
                    <th class="text-center">Crónicas</th>
                    <th style="width: 200px">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($enfermedades as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->nombre }}</td>
                        <td class="text-center"><strong>{{ $item->pacientes_count }}</strong></td>
                        <td class="text-center">{{ $item->cronicas_count }}</td>
                        <td>
                            <a class="btn btn-warning btn-sm"
                               href="{{ route('admin.enfermedades.edit', $item->id) }}">
                                Modificar
                            </a>

                            <button
                                type="button"
                                class="btn btn-danger btn-sm"
                                data-url="{{ route('admin.enfermedades.destroy', $item->id) }}"
                                data-token="{{ csrf_token() }}"
                                data-nombre="{{ $item->nombre }}"
                                onclick="destroy(this)">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            {{ $q !== '' ? 'No se encontraron enfermedades para "' . $q . '".' : 'Aún no hay enfermedades registradas.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted">
            Mostrando {{ $enfermedades->count() }} de {{ $enfermedades->total() }} enfermedad(es)
        </small>
        {{ $enfermedades->links() }}
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/enfermedad.js') }}"></script>
@endsection
