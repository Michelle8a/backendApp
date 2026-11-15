@extends('layouts.app')
@section('title', 'Detalle del Médico')
@section('content')
    @include('admin.partials.estilos')

    @include('admin.partials.alertas')

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1>{{ $medico->nombres }} {{ $medico->apellidos }}</h1>
            <p>JVPM {{ $medico->jvpm }}</p>
        </div>
        <span class="badge {{ $medico->verificado ? 'bg-success' : 'bg-warning text-dark' }} fs-6">
            {{ $medico->verificado ? 'Verificado' : 'Sin verificar' }}
        </span>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="panel">
                <h5 class="titulo-seccion mt-0">Cuenta</h5>
                <p class="dato-label">Correo</p>
                <p class="dato-valor">{{ $medico->usuario->correo ?? '—' }}</p>
                <p class="dato-label">Teléfono</p>
                <p class="dato-valor">{{ $medico->usuario->telefono ?: '—' }}</p>
                <p class="dato-label">Estado de la cuenta</p>
                <p class="dato-valor text-capitalize">{{ $medico->usuario->estado ?? '—' }}</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel">
                <h5 class="titulo-seccion mt-0">Perfil profesional</h5>
                <p class="dato-label">Centro de salud</p>
                <p class="dato-valor">{{ $medico->centro_salud->nombre ?? '—' }}</p>
                <p class="dato-label">Tipo</p>
                <p class="dato-valor">{{ $medico->es_general ? 'Médico general' : 'Especialista' }}</p>
                <div class="row">
                    <div class="col-4"><p class="dato-label">Tarifa</p><p class="dato-valor">${{ number_format($medico->tarifa, 2) }}</p></div>
                    <div class="col-4"><p class="dato-label">Calificación</p><p class="dato-valor">{{ number_format($medico->calificacion_promedio, 2) }}</p></div>
                    <div class="col-4"><p class="dato-label">Citas</p><p class="dato-valor">{{ $medico->citas_count }}</p></div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="panel">
                <p class="dato-label">Biografía</p>
                <p class="dato-valor fw-normal">{{ $medico->biografia ?: 'Sin biografía registrada.' }}</p>
                <p class="dato-label">Especialidades</p>
                <p>
                    @forelse ($medico->especialidades as $e)
                        <span class="badge badge-espec">{{ $e->nombre }}</span>
                    @empty
                        <span class="text-muted">Sin especialidades asignadas.</span>
                    @endforelse
                </p>
                <p class="dato-label">Idiomas</p>
                <p class="mb-0">
                    @forelse ($medico->idiomas as $i)
                        <span class="badge bg-secondary">{{ $i->nombre }}</span>
                    @empty
                        <span class="text-muted">Sin idiomas asignados.</span>
                    @endforelse
                </p>
            </div>
        </div>
    </div>

    {{-- Horarios de atención --}}
    <div class="panel" id="horarios">
        <h5 class="titulo-seccion mt-0">Horarios de atención</h5>

        <form action="{{ route('medico.horario.store', $medico->id) }}" method="POST" class="row g-2 align-items-end mb-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Día</label>
                <select name="dia_semana" class="form-select">
                    <option value="">-- Seleccione un día --</option>
                    @foreach ($dias as $num => $nombre)
                        <option value="{{ $num }}" {{ old('dia_semana') == $num ? 'selected' : '' }}>{{ $nombre }}</option>
                    @endforeach
                </select>
                @include('admin.partials.error', ['campo' => 'dia_semana'])
            </div>
            <div class="col-md-3">
                <label class="form-label">Hora de inicio</label>
                <input type="time" name="hora_inicio" class="form-control" value="{{ old('hora_inicio') }}">
                @include('admin.partials.error', ['campo' => 'hora_inicio'])
            </div>
            <div class="col-md-3">
                <label class="form-label">Hora de fin</label>
                <input type="time" name="hora_fin" class="form-control" value="{{ old('hora_fin') }}">
                @include('admin.partials.error', ['campo' => 'hora_fin'])
            </div>
            <div class="col-md-2">
                <button class="btn btn-azul w-100">Agregar horario</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-bordered tabla-admin">
                <thead>
                    <tr><th>Día</th><th>Hora de inicio</th><th>Hora de fin</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    @forelse ($medico->horarios as $h)
                        <tr>
                            <td>{{ $dias[$h->dia_semana] ?? $h->dia_semana }}</td>
                            <td>{{ substr($h->hora_inicio, 0, 5) }}</td>
                            <td>{{ substr($h->hora_fin, 0, 5) }}</td>
                            <td class="text-nowrap">
                                <button type="button" class="btn btn-warning btn-sm"
                                        data-url="{{ route('medico.horario.update', $h->id) }}"
                                        data-dia="{{ $h->dia_semana }}"
                                        data-inicio="{{ substr($h->hora_inicio, 0, 5) }}"
                                        data-fin="{{ substr($h->hora_fin, 0, 5) }}"
                                        onclick="editarHorario(this)">Modificar</button>
                                <button type="button" class="btn btn-danger btn-sm"
                                        data-url="{{ route('medico.horario.destroy', $h->id) }}"
                                        data-token="{{ csrf_token() }}"
                                        data-detalle="{{ $dias[$h->dia_semana] ?? '' }} de {{ substr($h->hora_inicio, 0, 5) }} a {{ substr($h->hora_fin, 0, 5) }}"
                                        onclick="destroyHorario(this)">Eliminar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">
                                Este médico aún no tiene horarios. Agrega el primero con el formulario de arriba.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <a href="{{ route('medico.edit', $medico->id) }}" class="btn btn-warning">Modificar</a>
    <a href="{{ route('medico.index') }}" class="btn btn-secondary">Volver al listado</a>
@endsection

@section('scripts')
    {{-- Modal para modificar un horario --}}
    <div class="modal fade" id="modalHorario" tabindex="-1" aria-labelledby="modalHorarioTitulo" aria-hidden="true">
        <div class="modal-dialog">
            <form id="formHorario" method="POST" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header" style="background:#060E69;color:#fff;">
                    <h5 class="modal-title" id="modalHorarioTitulo">Modificar horario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->horarioEdit->any())
                        <div class="alert alert-danger py-2">
                            @foreach ($errors->horarioEdit->all() as $mensaje)
                                <div>{{ $mensaje }}</div>
                            @endforeach
                        </div>
                    @endif
                    <label class="form-label">Día</label>
                    <select name="dia_semana" id="hDia" class="form-select mb-3">
                        @foreach ($dias as $num => $nombre)
                            <option value="{{ $num }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Hora de inicio</label>
                            <input type="time" name="hora_inicio" id="hInicio" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Hora de fin</label>
                            <input type="time" name="hora_fin" id="hFin" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-azul">Guardar cambios</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/medico.js') }}"></script>

    {{-- Si la validación falló, reabre el modal con lo que escribió el admin --}}
    @if (session('horario_editando'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                abrirModalHorario(
                    @json(route('medico.horario.update', session('horario_editando'))),
                    @json(old('dia_semana')),
                    @json(old('hora_inicio')),
                    @json(old('hora_fin'))
                );
            });
        </script>
    @endif
@endsection
