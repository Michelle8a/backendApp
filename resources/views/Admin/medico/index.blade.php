@extends('layouts.app')
@section('title', 'Médicos')
@section('content')
    @include('admin.partials.estilos')

    <div class="page-header">
        <h1>Médicos</h1>
        <p>Administra los perfiles médicos, su verificación y sus datos de contacto.</p>
    </div>

    @include('admin.partials.alertas')

    {{-- Filtros --}}
    <form method="GET" action="{{ route('medico.index') }}" class="panel">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Buscar</label>
                <input type="text" name="q" class="form-control" value="{{ request('q') }}"
                       placeholder="Nombre, apellido, JVPM o correo">
            </div>
            <div class="col-md-3">
                <label class="form-label">Centro de salud</label>
                <select name="centro_salud_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($centros as $c)
                        <option value="{{ $c->id }}" {{ request('centro_salud_id') == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Especialidad</label>
                <select name="especialidad_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach ($especialidades as $e)
                        <option value="{{ $e->id }}" {{ request('especialidad_id') == $e->id ? 'selected' : '' }}>{{ $e->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Verificación</label>
                <select name="verificado" class="form-select">
                    <option value="">Todos</option>
                    <option value="1" {{ request('verificado') === '1' ? 'selected' : '' }}>Verificados</option>
                    <option value="0" {{ request('verificado') === '0' ? 'selected' : '' }}>Sin verificar</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo</label>
                <select name="es_general" class="form-select">
                    <option value="">Todos</option>
                    <option value="1" {{ request('es_general') === '1' ? 'selected' : '' }}>Médico general</option>
                    <option value="0" {{ request('es_general') === '0' ? 'selected' : '' }}>Especialista</option>
                </select>
            </div>
            <div class="col-md-9 d-flex gap-2">
                <button class="btn btn-azul">Buscar</button>
                <a href="{{ route('medico.index') }}" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a class="btn btn-success btn-sm" href="{{ route('medico.create') }}">Agregar nuevo médico</a>
        <a class="btn btn-danger btn-sm" target="_blank"
           href="{{ route('medico.pdf', request()->query()) }}">Descargar reporte PDF</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-bordered tabla-admin">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Médico</th>
                    <th>JVPM</th>
                    <th>Centro de salud</th>
                    <th>Especialidades</th>
                    <th>Tarifa</th>
                    <th>Calificación</th>
                    <th class="text-center">Verificado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($medicos as $m)
                    <tr>
                        <td>{{ $m->id }}</td>
                        <td>
                            <strong>{{ $m->apellidos }}, {{ $m->nombres }}</strong><br>
                            <small class="text-muted">{{ $m->usuario->correo ?? 'Sin cuenta' }}</small>
                        </td>
                        <td>{{ $m->jvpm }}</td>
                        <td>{{ $m->centro_salud->nombre ?? 'Sin centro' }}</td>
                        <td>
                            @if ($m->es_general)
                                <span class="badge bg-secondary">Médico general</span>
                            @endif
                            @foreach ($m->especialidades as $e)
                                <span class="badge badge-espec">{{ $e->nombre }}</span>
                            @endforeach
                            @if (! $m->es_general && $m->especialidades->isEmpty())
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>${{ number_format($m->tarifa, 2) }}</td>
                        <td>{{ number_format($m->calificacion_promedio, 2) }}</td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       {{ $m->verificado ? 'checked' : '' }}
                                       data-url="{{ route('medico.verificar', $m->id) }}"
                                       data-token="{{ csrf_token() }}"
                                       onchange="toggleVerificado(this)"
                                       aria-label="Verificado">
                            </div>
                        </td>
                        <td class="text-nowrap">
                            <a class="btn btn-info btn-sm text-white" href="{{ route('medico.show', $m->id) }}">Ver</a>
                            <a class="btn btn-warning btn-sm" href="{{ route('medico.edit', $m->id) }}">Modificar</a>
                            <button type="button" class="btn btn-danger btn-sm"
                                    data-url="{{ route('medico.destroy', $m->id) }}"
                                    data-token="{{ csrf_token() }}"
                                    data-nombre="{{ $m->nombres }} {{ $m->apellidos }}"
                                    onclick="destroy(this)">Eliminar</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            No se encontraron médicos con esos filtros. Prueba limpiar la búsqueda o agrega un médico nuevo.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Mostrando {{ $medicos->firstItem() ?? 0 }}–{{ $medicos->lastItem() ?? 0 }} de {{ $medicos->total() }} médicos</small>
        {{ $medicos->links('pagination::bootstrap-5') }}
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/medico.js') }}"></script>
@endsection
