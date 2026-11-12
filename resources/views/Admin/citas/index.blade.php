@extends('layouts.app')
@section('title', 'Reporte de citas')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-header">
        <h1>Citas</h1>
        <p>Consulta las citas del sistema y descarga el reporte. Es de solo lectura.</p>
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
        <div class="admin-card-title">Filtrar citas</div>
        <div class="admin-card-body">
            <form action="{{ route('citas.index') }}" method="GET">
                <div class="row g-2">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label mb-1" for="q">Paciente</label>
                        <input type="text" id="q" name="q" class="form-control"
                               value="{{ request('q') }}" placeholder="Nombre o apellido">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label mb-1" for="medico_id">Médico</label>
                        <select id="medico_id" name="medico_id" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($medicos as $medico)
                                <option value="{{ $medico->id }}" {{ (string) request('medico_id') === (string) $medico->id ? 'selected' : '' }}>
                                    {{ $medico->nombres }} {{ $medico->apellidos }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label mb-1" for="estado">Estado</label>
                        <select id="estado" name="estado" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($estados as $val => $txt)
                                <option value="{{ $val }}" {{ request('estado') === $val ? 'selected' : '' }}>{{ $txt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label mb-1" for="tipo">Tipo</label>
                        <select id="tipo" name="tipo" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($tipos as $val => $txt)
                                <option value="{{ $val }}" {{ request('tipo') === $val ? 'selected' : '' }}>{{ $txt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label mb-1" for="desde">Desde</label>
                        <input type="date" id="desde" name="desde" class="form-control" value="{{ request('desde') }}">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label mb-1" for="hasta">Hasta</label>
                        <input type="date" id="hasta" name="hasta" class="form-control" value="{{ request('hasta') }}">
                    </div>
                    <div class="col-lg-6 d-flex align-items-end gap-2">
                        <button class="btn btn-brand">Filtrar</button>
                        <a href="{{ route('citas.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Resumen por estado --}}
    <div class="row g-2 mb-3">
        @foreach ($estados as $val => $txt)
            <div class="col">
                <div class="admin-card mb-0"><div class="admin-card-body">
                    <div class="dato-label">{{ $txt }}</div>
                    <div class="dato-valor mb-0">{{ $resumen[$val] }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <span class="text-muted">
            {{ $citas->total() }} {{ $citas->total() === 1 ? 'cita encontrada' : 'citas encontradas' }}
        </span>
        <a class="btn btn-danger btn-sm" href="{{ route('citas.pdf', request()->query()) }}">
            Descargar reporte PDF
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-admin table-bordered mb-3">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Fecha y hora</th>
                    <th>Paciente</th>
                    <th>Médico</th>
                    <th>Tipo</th>
                    <th>Duración</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($citas as $item)
                    @php
                        $color = [
                            'pendiente'  => 'warning text-dark',
                            'confirmada' => 'info text-dark',
                            'en_curso'   => 'primary',
                            'cancelada'  => 'danger',
                            'finalizada' => 'success',
                        ][$item->estado] ?? 'secondary';
                    @endphp
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->fecha_hora)->format('d/m/Y H:i') }}</td>
                        <td>{{ $item->paciente ? $item->paciente->nombres . ' ' . $item->paciente->apellidos : '—' }}</td>
                        <td>{{ $item->medico ? $item->medico->nombres . ' ' . $item->medico->apellidos : '—' }}</td>
                        <td>{{ $tipos[$item->tipo] ?? $item->tipo }}</td>
                        <td>{{ $item->duracion_min }} min</td>
                        <td><span class="badge bg-{{ $color }}">{{ $estados[$item->estado] ?? $item->estado }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">No hay citas que coincidan con los filtros.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $citas->links('pagination::bootstrap-5') }}
@endsection
