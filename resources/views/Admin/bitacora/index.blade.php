@extends('layouts.app')
@section('title', 'Bitácora de auditoría')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-header">
        <h1>Bitácora de auditoría</h1>
        <p>Registro de las acciones realizadas en el sistema. Es de solo lectura.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Filtros --}}
    <div class="admin-card">
        <div class="admin-card-title">Filtrar registros</div>
        <div class="admin-card-body">
            <form action="{{ route('bitacora.index') }}" method="GET">
                <div class="row g-2">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label mb-1" for="q">Correo del usuario</label>
                        <input type="text" id="q" name="q" class="form-control"
                               value="{{ request('q') }}" placeholder="Ej: admin@correo.com">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1" for="accion">Acción</label>
                        <select id="accion" name="accion" class="form-select">
                            <option value="">Todas</option>
                            @foreach ($acciones as $accion)
                                <option value="{{ $accion }}" {{ request('accion') === $accion ? 'selected' : '' }}>{{ ucfirst($accion) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1" for="recurso">Recurso</label>
                        <select id="recurso" name="recurso" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($recursos as $recurso)
                                <option value="{{ $recurso }}" {{ request('recurso') === $recurso ? 'selected' : '' }}>{{ ucfirst($recurso) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1" for="desde">Desde</label>
                        <input type="date" id="desde" name="desde" class="form-control" value="{{ request('desde') }}">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1" for="hasta">Hasta</label>
                        <input type="date" id="hasta" name="hasta" class="form-control" value="{{ request('hasta') }}">
                    </div>
                    <div class="col-lg-1 col-md-6 d-flex align-items-end">
                        <button class="btn btn-brand w-100">Filtrar</button>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="{{ route('bitacora.index') }}" class="btn btn-outline-secondary btn-sm">Limpiar filtros</a>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <span class="text-muted">
            {{ $registros->total() }} {{ $registros->total() === 1 ? 'registro encontrado' : 'registros encontrados' }}
        </span>
        <a class="btn btn-danger btn-sm" href="{{ route('bitacora.pdf', request()->query()) }}">
            Descargar reporte PDF
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-admin table-bordered mb-3">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Fecha y hora</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Recurso</th>
                    <th>ID recurso</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registros as $item)
                    @php
                        $color = [
                            'crear' => 'success',
                            'actualizar' => 'warning text-dark',
                            'eliminar' => 'danger',
                            'login' => 'info text-dark',
                        ][$item->accion] ?? 'secondary';
                    @endphp
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->fecha?->format('d/m/Y H:i:s') }}</td>
                        <td>
                            {{ $item->usuario->correo ?? 'Usuario #' . $item->usuario_id }}
                            @if ($item->usuario)
                                <div class="text-muted small">{{ ucfirst($item->usuario->rol) }}</div>
                            @endif
                        </td>
                        <td><span class="badge bg-{{ $color }}">{{ ucfirst($item->accion) }}</span></td>
                        <td>{{ ucfirst($item->recurso) }}</td>
                        <td>{{ $item->recurso_id }}</td>
                        <td>{{ $item->ip }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">
                            No hay registros que coincidan con los filtros.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $registros->links('pagination::bootstrap-5') }}
@endsection
