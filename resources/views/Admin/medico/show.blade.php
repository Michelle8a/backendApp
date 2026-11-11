@extends('layouts.app')
@section('title', 'Detalle del Médico')
@section('content')
    @include('admin.partials.estilos')

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

    <a href="{{ route('medico.edit', $medico->id) }}" class="btn btn-warning">Modificar</a>
    <a href="{{ route('medico.index') }}" class="btn btn-secondary">Volver al listado</a>
@endsection
