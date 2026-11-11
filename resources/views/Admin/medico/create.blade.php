@extends('layouts.app')
@section('title', 'Crear Médico')
@section('content')
    @include('admin.partials.estilos')

    <div class="page-header">
        <h1>Agregar médico</h1>
        <p>Se creará la cuenta de acceso y el perfil profesional en un solo paso.</p>
    </div>

    <div class="panel">
        <form action="{{ route('medico.store') }}" method="POST">
            @csrf
            @include('admin.medico._form')
            <div class="mt-4">
                <button class="btn btn-azul">Guardar médico</button>
                <a href="{{ route('medico.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
