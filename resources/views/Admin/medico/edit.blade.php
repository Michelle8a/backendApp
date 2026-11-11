@extends('layouts.app')
@section('title', 'Actualizar Médico')
@section('content')
    @include('admin.partials.estilos')

    <div class="page-header">
        <h1>Modificar médico</h1>
        <p>{{ $medico->nombres }} {{ $medico->apellidos }} · JVPM {{ $medico->jvpm }}</p>
    </div>

    <div class="panel">
        <form action="{{ route('medico.update', $medico->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.medico._form')
            <div class="mt-4">
                <button class="btn btn-azul">Actualizar médico</button>
                <a href="{{ route('medico.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
