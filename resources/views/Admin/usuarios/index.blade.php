@extends('layouts.app')
@section('title', 'Usuarios')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-header">
        <h1>Usuarios</h1>
        <p>Administra las cuentas del sistema: crea, edita, activa o desactiva su acceso.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    {{-- Filtros --}}
    <div class="admin-card">
        <div class="admin-card-title">Buscar usuarios</div>
        <div class="admin-card-body">
            <form action="{{ route('admin.usuarios.index') }}" method="GET">
                <div class="row g-2">
                    <div class="col-lg-5 col-md-12">
                        <label class="form-label mb-1" for="q">Correo o teléfono</label>
                        <input type="text" id="q" name="q" class="form-control"
                               value="{{ request('q') }}" placeholder="Ej: correo@ejemplo.com">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1" for="rol">Rol</label>
                        <select id="rol" name="rol" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($roles as $val => $txt)
                                <option value="{{ $val }}" {{ request('rol') === $val ? 'selected' : '' }}>{{ $txt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label mb-1" for="estado">Estado</label>
                        <select id="estado" name="estado" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($estados as $val => $txt)
                                <option value="{{ $val }}" {{ request('estado') === $val ? 'selected' : '' }}>{{ $txt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 d-flex align-items-end gap-2">
                        <button class="btn btn-brand w-100">Buscar</button>
                        <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <a class="btn btn-success btn-sm" href="{{ route('admin.usuarios.create') }}">Agregar nuevo usuario</a>
            <span class="text-muted">
                {{ $usuarios->total() }} {{ $usuarios->total() === 1 ? 'usuario encontrado' : 'usuarios encontrados' }}
            </span>
        </div>
        <a class="btn btn-danger btn-sm" href="{{ route('admin.usuarios.pdf', request()->query()) }}">
            Descargar reporte PDF
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-admin table-bordered mb-3">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th>Correo verificado</th>
                    <th>Estado</th>
                    <th>Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $item)
                    @php
                        $rolColor = ['admin' => 'dark', 'medico' => 'info', 'paciente' => 'secondary'][$item->rol] ?? 'secondary';
                        $activo = $item->estado === 'activo';
                        $esYo = $item->id === auth()->id();
                    @endphp
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->correo }}</td>
                        <td>{{ $item->telefono }}</td>
                        <td><span class="badge bg-{{ $rolColor }}">{{ $roles[$item->rol] ?? $item->rol }}</span></td>
                        <td>
                            @if ($item->correo_verificado_en)
                                <span class="badge bg-success">Verificado</span>
                            @else
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $activo ? 'success' : 'danger' }}">
                                {{ $estados[$item->estado] ?? $item->estado }}
                            </span>
                        </td>
                        <td>{{ $item->created_at?->format('d/m/Y') }}</td>
                        <td class="text-nowrap">
                            <a class="btn btn-warning btn-sm" href="{{ route('admin.usuarios.edit', $item->id) }}">
                                Modificar
                            </a>

                            @if ($esYo)
                                <span class="text-muted small ms-1">Tu cuenta</span>
                            @else
                                <button type="button"
                                    class="btn btn-sm {{ $activo ? 'btn-secondary' : 'btn-success' }}"
                                    data-url="{{ route('admin.usuarios.estado', $item->id) }}"
                                    data-token="{{ csrf_token() }}"
                                    data-nuevo="{{ $activo ? 'suspendido' : 'activo' }}"
                                    data-correo="{{ $item->correo }}"
                                    onclick="cambiarEstado(this)">
                                    {{ $activo ? 'Desactivar' : 'Activar' }}
                                </button>

                                <button type="button" class="btn btn-danger btn-sm"
                                    data-url="{{ route('admin.usuarios.destroy', $item->id) }}"
                                    data-token="{{ csrf_token() }}"
                                    data-correo="{{ $item->correo }}"
                                    onclick="destroy(this)">
                                    Eliminar
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state">
                            No hay usuarios que coincidan con la búsqueda. Prueba con otro correo o quita los filtros.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $usuarios->links('pagination::bootstrap-5') }}
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/usuario.js') }}"></script>
@endsection
