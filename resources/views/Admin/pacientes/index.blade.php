@extends('layouts.app')
@section('title', 'Pacientes')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-header">
        <h1>Pacientes</h1>
        <p>Consulta y busca los pacientes registrados en el sistema.</p>
    </div>

    {{-- Filtros --}}
    <div class="admin-card">
        <div class="admin-card-title">Buscar pacientes</div>
        <div class="admin-card-body">
            <form action="{{ route('admin.pacientes.index') }}" method="GET">
                <div class="row g-2">
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label mb-1" for="q">Nombre, correo o teléfono</label>
                        <input type="text" id="q" name="q" class="form-control"
                               value="{{ request('q') }}" placeholder="Ej: María López">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1" for="sexo">Sexo</label>
                        <select id="sexo" name="sexo" class="form-select">
                            <option value="">Todos</option>
                            @foreach (['M' => 'Masculino', 'F' => 'Femenino', 'Otro' => 'Otro'] as $val => $txt)
                                <option value="{{ $val }}" {{ request('sexo') === $val ? 'selected' : '' }}>{{ $txt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1" for="tipo_sangre">Tipo de sangre</label>
                        <select id="tipo_sangre" name="tipo_sangre" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($tiposSangre as $tipo)
                                <option value="{{ $tipo }}" {{ request('tipo_sangre') === $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1" for="idioma_id">Idioma</label>
                        <select id="idioma_id" name="idioma_id" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($idiomas as $idioma)
                                <option value="{{ $idioma->id }}" {{ (string) request('idioma_id') === (string) $idioma->id ? 'selected' : '' }}>
                                    {{ $idioma->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 d-flex align-items-end gap-2">
                        <button class="btn btn-brand w-100">Buscar</button>
                        <a href="{{ route('admin.pacientes.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Barra de resultados + reporte --}}
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <span class="text-muted">
            {{ $pacientes->total() }} {{ $pacientes->total() === 1 ? 'paciente encontrado' : 'pacientes encontrados' }}
        </span>
        <a class="btn btn-danger btn-sm" href="{{ route('admin.pacientes.pdf', request()->query()) }}">
            Descargar reporte PDF
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-admin table-bordered mb-3">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Paciente</th>
                    <th>Edad</th>
                    <th>Sexo</th>
                    <th>Sangre</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Idioma</th>
                    <th class="text-center">Citas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pacientes as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->nombres }} {{ $item->apellidos }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->fecha_nacimiento)->age }} años</td>
                        <td>{{ $item->sexo }}</td>
                        <td>{{ $item->tipo_sangre }}</td>
                        <td>{{ $item->usuario->correo ?? 'Sin cuenta' }}</td>
                        <td>{{ $item->usuario->telefono ?? '—' }}</td>
                        <td>{{ $item->idioma->nombre ?? '—' }}</td>
                        <td class="text-center">{{ $item->citas_count }}</td>
                        <td>
                            <a class="btn btn-outline-brand btn-sm" href="{{ route('admin.pacientes.show', $item->id) }}">
                                Ver detalle
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="empty-state">
                            No hay pacientes que coincidan con la búsqueda. Prueba con otro nombre o quita los filtros.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $pacientes->links('pagination::bootstrap-5') }}
@endsection
