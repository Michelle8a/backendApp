@extends('layouts.app')
@section('title', 'Detalle del paciente')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1>{{ $paciente->nombres }} {{ $paciente->apellidos }}</h1>
            <p>Paciente #{{ $paciente->id }}, registrado el {{ $paciente->created_at?->format('d/m/Y') }}</p>
        </div>
        <a href="{{ route('admin.pacientes.index') }}" class="btn btn-light btn-sm">Volver al listado</a>
    </div>

    <div class="row">
        {{-- Datos personales --}}
        <div class="col-lg-6">
            <div class="admin-card">
                <div class="admin-card-title">Datos personales</div>
                <div class="admin-card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="dato-label">Fecha de nacimiento</div>
                            <div class="dato-valor">
                                {{ \Carbon\Carbon::parse($paciente->fecha_nacimiento)->format('d/m/Y') }}
                                ({{ \Carbon\Carbon::parse($paciente->fecha_nacimiento)->age }} años)
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="dato-label">DUI</div>
                            <div class="dato-valor">{{ $duiEnmascarado }}</div>
                        </div>
                        <div class="col-6">
                            <div class="dato-label">Sexo</div>
                            <div class="dato-valor">{{ $paciente->sexo }}</div>
                        </div>
                        <div class="col-6">
                            <div class="dato-label">Tipo de sangre</div>
                            <div class="dato-valor">{{ $paciente->tipo_sangre }}</div>
                        </div>
                        <div class="col-6">
                            <div class="dato-label">Idioma</div>
                            <div class="dato-valor">{{ $paciente->idioma->nombre ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cuenta y ubicación --}}
        <div class="col-lg-6">
            <div class="admin-card">
                <div class="admin-card-title">Cuenta y ubicación</div>
                <div class="admin-card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="dato-label">Correo</div>
                            <div class="dato-valor">{{ $paciente->usuario->correo ?? 'Sin cuenta' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="dato-label">Teléfono</div>
                            <div class="dato-valor">{{ $paciente->usuario->telefono ?? '—' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="dato-label">Dirección</div>
                            <div class="dato-valor">{{ $paciente->direccion }}</div>
                        </div>
                        <div class="col-12">
                            <a class="btn btn-outline-brand btn-sm" target="_blank" rel="noopener"
                               href="https://www.google.com/maps?q={{ $paciente->latitud }},{{ $paciente->longitud }}">
                                Ver ubicación en el mapa
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Resumen --}}
    <div class="row">
        <div class="col-md-6">
            <div class="admin-card"><div class="admin-card-body">
                <div class="dato-label">Citas registradas</div>
                <div class="dato-valor mb-0">{{ $paciente->citas_count }}</div>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="admin-card"><div class="admin-card-body">
                <div class="dato-label">Contactos de emergencia</div>
                <div class="dato-valor mb-0">{{ $paciente->contactos_emergencia_count }}</div>
            </div></div>
        </div>
    </div>

    {{-- Alergias y enfermedades --}}
    <div class="row">
        <div class="col-lg-6">
            <div class="admin-card">
                <div class="admin-card-title">Alergias</div>
                <div class="admin-card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th>Alergia</th><th>Gravedad</th></tr></thead>
                        <tbody>
                            @forelse ($paciente->alergias as $alergia)
                                @php
                                    $color = ['leve' => 'success', 'moderada' => 'warning', 'grave' => 'danger'][$alergia->pivot->gravedad] ?? 'secondary';
                                @endphp
                                <tr>
                                    <td>{{ $alergia->nombre }}</td>
                                    <td><span class="badge bg-{{ $color }}">{{ ucfirst($alergia->pivot->gravedad) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="empty-state">Sin alergias registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="admin-card">
                <div class="admin-card-title">Enfermedades</div>
                <div class="admin-card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th>Enfermedad</th><th>Desde</th><th>Crónica</th></tr></thead>
                        <tbody>
                            @forelse ($paciente->enfermedades as $enf)
                                <tr>
                                    <td>{{ $enf->nombre }}</td>
                                    <td>{{ \Carbon\Carbon::parse($enf->pivot->desde)->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $enf->pivot->es_cronica ? 'danger' : 'secondary' }}">
                                            {{ $enf->pivot->es_cronica ? 'Sí' : 'No' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="empty-state">Sin enfermedades registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
