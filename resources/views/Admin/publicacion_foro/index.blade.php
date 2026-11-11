@extends('layouts.app')
@section('title', 'Publicaciones del foro')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-header">
        <h1>Publicaciones del foro</h1>
        <p>Monitorea lo que se publica, oculta contenido inapropiado y genera reportes para su revisión.</p>
    </div>

    {{-- Filtros --}}
    <div class="admin-card">
        <form action="{{ route('publicacion_foro.show') }}" method="GET" class="admin-filters">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="buscar" class="form-label">Buscar</label>
                    <input type="search" id="buscar" name="buscar" class="form-control form-control-sm"
                           maxlength="100" placeholder="Título, contenido o correo del autor"
                           value="{{ request('buscar') }}">
                </div>
                <div class="col-6 col-md-4">
                    <label for="categoria_id" class="form-label">Categoría</label>
                    <select id="categoria_id" name="categoria_id" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        @foreach ($categorias as $cat)
                            <option value="{{ $cat->id }}" {{ (string) request('categoria_id') === (string) $cat->id ? 'selected' : '' }}>
                                {{ $cat->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="anonimo" class="form-label">Anonimato</label>
                    <select id="anonimo" name="anonimo" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="1" {{ request('anonimo') === '1' ? 'selected' : '' }}>Anónimas</option>
                        <option value="0" {{ request('anonimo') === '0' ? 'selected' : '' }}>Con autor visible</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="visibilidad" class="form-label">Visibilidad</label>
                    <select id="visibilidad" name="visibilidad" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="visibles" {{ request('visibilidad') === 'visibles' ? 'selected' : '' }}>Visibles</option>
                        <option value="ocultas" {{ request('visibilidad') === 'ocultas' ? 'selected' : '' }}>Ocultas</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label for="desde" class="form-label">Desde</label>
                    <input type="date" id="desde" name="desde" class="form-control form-control-sm"
                           value="{{ request('desde') }}">
                </div>
                <div class="col-6 col-md-3">
                    <label for="hasta" class="form-label">Hasta</label>
                    <input type="date" id="hasta" name="hasta" class="form-control form-control-sm"
                           value="{{ request('hasta') }}">
                </div>
                <div class="col-12 col-md-6 d-flex gap-2">
                    <button class="btn btn-admin btn-sm">Filtrar</button>
                    <a href="{{ route('publicacion_foro.show') }}" class="btn btn-secondary btn-sm">Limpiar</a>
                </div>
            </div>

            @if ($errors->any())
                <div class="mt-3">
                    @foreach ($errors->all() as $error)
                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $error }}</strong></span>
                    @endforeach
                </div>
            @endif
        </form>
    </div>

    {{-- Resultados --}}
    <div class="admin-card">
        <div class="admin-toolbar">
            <span class="admin-summary">
                {{ $publicaciones->total() }}
                {{ $publicaciones->total() === 1 ? 'publicación encontrada' : 'publicaciones encontradas' }}
            </span>

            <a class="btn btn-outline-admin btn-sm"
               href="{{ route('publicacion_foro.pdf', request()->query()) }}">
                Descargar reporte PDF
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-bordered table-admin">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Autor</th>
                        <th>Fecha</th>
                        <th class="text-center">Comentarios</th>
                        <th class="text-center">Me gusta</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($publicaciones as $item)
                        @php
                            $autor = $item->usuario
                                ? $item->usuario->correo . ' (' . $item->usuario->rol . ')'
                                : 'Usuario eliminado';
                            $fecha = \Illuminate\Support\Carbon::parse($item->creado_en)->format('d/m/Y H:i');
                            $ocultaInfo = $item->oculta
                                ? 'Oculta desde el ' . optional($item->oculta_en)->format('d/m/Y H:i')
                                    . ($item->moderador ? ' por ' . $item->moderador->correo : '')
                                    . '. Los demás usuarios no la ven en la app.'
                                : '';
                        @endphp
                        <tr class="{{ $item->oculta ? 'fila-oculta' : '' }}">
                            <td>{{ $item->id }}</td>
                            <td><div class="cell-clamp">{{ $item->titulo }}</div></td>
                            <td>{{ $item->categoria_foro->nombre ?? 'Sin categoría' }}</td>
                            <td>
                                {{ $item->usuario->correo ?? 'Usuario eliminado' }}
                                @if ($item->usuario)
                                    <span class="badge badge-admin">{{ $item->usuario->rol }}</span>
                                @endif
                                @if ($item->anonimo)
                                    <span class="badge bg-secondary" title="Los demás usuarios no ven quién la publicó">Anónima</span>
                                @endif
                            </td>
                            <td>{{ $fecha }}</td>
                            <td class="text-center">{{ $item->comentarios_count }}</td>
                            <td class="text-center">{{ $item->me_gusta_count }}</td>
                            <td>
                                @if ($item->oculta)
                                    <span class="badge bg-warning text-dark" title="{{ $ocultaInfo }}">Oculta</span>
                                @else
                                    <span class="badge bg-success">Visible</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <button type="button" class="btn btn-info btn-sm text-white"
                                        data-bs-toggle="modal" data-bs-target="#modalPublicacion"
                                        data-titulo="{{ $item->titulo }}"
                                        data-contenido="{{ $item->contenido }}"
                                        data-categoria="{{ $item->categoria_foro->nombre ?? 'Sin categoría' }}"
                                        data-autor="{{ $autor }}"
                                        data-anonimo="{{ $item->anonimo ? 1 : 0 }}"
                                        data-fecha="{{ $fecha }}"
                                        data-comentarios="{{ $item->comentarios_count }}"
                                        data-megusta="{{ $item->me_gusta_count }}"
                                        data-oculta="{{ $item->oculta ? 1 : 0 }}"
                                        data-oculta-info="{{ $ocultaInfo }}">
                                    Ver
                                </button>

                                <button type="button"
                                        class="btn btn-sm {{ $item->oculta ? 'btn-success' : 'btn-warning' }}"
                                        data-url="{{ route('publicacion_foro.visibilidad', $item->id) }}"
                                        data-token="{{ csrf_token() }}"
                                        data-titulo="{{ $item->titulo }}"
                                        data-ocultar="{{ $item->oculta ? 0 : 1 }}"
                                        onclick="cambiarVisibilidad(this)">
                                    {{ $item->oculta ? 'Mostrar' : 'Ocultar' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="admin-empty">
                                No hay publicaciones con esos filtros. Prueba ampliando el rango de fechas o limpiando la búsqueda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 d-flex justify-content-center">
            {{ $publicaciones->links('pagination::bootstrap-5') }}
        </div>
    </div>

    {{-- Modal de detalle --}}
    <div class="modal fade admin-modal" id="modalPublicacion" tabindex="-1" aria-labelledby="modalPublicacionTitulo" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPublicacionTitulo">Detalle de la publicación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning d-none" data-campo="oculta" role="status"></div>
                    <h4 class="fw-bold mb-1" data-campo="titulo"></h4>
                    <p class="text-muted mb-3">
                        <span data-campo="categoria"></span> ·
                        <span data-campo="fecha"></span>
                        <span class="badge bg-secondary d-none" data-campo="anonimo">Anónima</span>
                    </p>
                    <p class="mb-3"><strong>Autor:</strong> <span data-campo="autor"></span></p>
                    <div class="contenido-completo mb-3" data-campo="contenido"></div>
                    <hr>
                    <span class="badge badge-admin">Comentarios: <span data-campo="comentarios"></span></span>
                    <span class="badge badge-admin">Me gusta: <span data-campo="megusta"></span></span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/publicacion_foro.js') }}"></script>
@endsection
