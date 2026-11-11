@extends('layouts.app')
@section('title', 'Comentarios del foro')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-header">
        <h1>Comentarios del foro</h1>
        <p>Revisa lo que comenta la gente y elimina contenido ofensivo o inapropiado.</p>
    </div>

    {{-- Filtros --}}
    <div class="admin-card">
        <form action="{{ route('comentario_foro.show') }}" method="GET" class="admin-filters">
            @if (request('publicacion_id'))
                <input type="hidden" name="publicacion_id" value="{{ request('publicacion_id') }}">
            @endif

            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label for="buscar" class="form-label">Buscar</label>
                    <input type="search" id="buscar" name="buscar" class="form-control form-control-sm"
                           maxlength="100" placeholder="Texto del comentario, correo del autor o título de la publicación"
                           value="{{ request('buscar') }}">
                </div>
                <div class="col-6 col-md-3">
                    <label for="rol" class="form-label">Rol del autor</label>
                    <select id="rol" name="rol" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="paciente" {{ request('rol') === 'paciente' ? 'selected' : '' }}>Paciente</option>
                        <option value="medico" {{ request('rol') === 'medico' ? 'selected' : '' }}>Médico</option>
                        <option value="admin" {{ request('rol') === 'admin' ? 'selected' : '' }}>Administrador</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="desde" class="form-label">Desde</label>
                    <input type="date" id="desde" name="desde" class="form-control form-control-sm"
                           value="{{ request('desde') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label for="hasta" class="form-label">Hasta</label>
                    <input type="date" id="hasta" name="hasta" class="form-control form-control-sm"
                           value="{{ request('hasta') }}">
                </div>
                <div class="col-6 col-md-12 d-flex gap-2">
                    <button class="btn btn-admin btn-sm">Filtrar</button>
                    <a href="{{ route('comentario_foro.show') }}" class="btn btn-secondary btn-sm">Limpiar</a>
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
        @if ($publicacionFiltro)
            <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2" role="status">
                <span>Mostrando solo los comentarios de la publicación <strong>#{{ $publicacionFiltro->id }} {{ $publicacionFiltro->titulo }}</strong>.</span>
                <a class="btn btn-outline-admin btn-sm"
                   href="{{ route('comentario_foro.show', request()->except(['publicacion_id', 'page'])) }}">
                    Ver todos los comentarios
                </a>
            </div>
        @endif

        <div class="admin-toolbar">
            <span class="admin-summary">
                {{ $comentarios->total() }}
                {{ $comentarios->total() === 1 ? 'comentario encontrado' : 'comentarios encontrados' }}
            </span>

            <a class="btn btn-outline-admin btn-sm"
               href="{{ route('comentario_foro.pdf', request()->query()) }}">
                Descargar reporte PDF
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-bordered table-admin">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Comentario</th>
                        <th>Publicación</th>
                        <th>Autor</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($comentarios as $item)
                        @php
                            $pub = $item->publicacion;
                            $fecha = \Illuminate\Support\Carbon::parse($item->creado_en)->format('d/m/Y H:i');
                            $autorTexto = ($item->autor->correo ?? 'Usuario eliminado')
                                . ($item->autor ? ' (' . $item->autor->rol . ')' : '')
                                . ($item->autor_nombre ? ' · ' . $item->autor_nombre : '');
                        @endphp
                        <tr class="{{ $pub && $pub->oculta ? 'fila-oculta' : '' }}">
                            <td>{{ $item->id }}</td>
                            <td><div class="cell-clamp">{{ $item->contenido }}</div></td>
                            <td>
                                @if ($pub)
                                    <a href="{{ route('comentario_foro.show', ['publicacion_id' => $pub->id]) }}"
                                       class="text-decoration-none" title="Ver todos los comentarios de esta publicación">
                                        <div class="cell-clamp">{{ $pub->titulo }}</div>
                                    </a>
                                    <small class="text-muted">{{ $pub->categoria_foro->nombre ?? 'Sin categoría' }}</small>
                                    @if ($pub->oculta)
                                        <span class="badge bg-warning text-dark">Publicación oculta</span>
                                    @endif
                                @else
                                    <span class="text-muted">Publicación eliminada</span>
                                @endif
                            </td>
                            <td>
                                {{ $item->autor->correo ?? 'Usuario eliminado' }}
                                @if ($item->autor)
                                    <span class="badge badge-admin">{{ $item->autor->rol }}</span>
                                @endif
                                @if ($item->autor_nombre)
                                    <br><small class="text-muted">{{ $item->autor_nombre }}</small>
                                @endif
                                @if ($item->anonimo_en_app)
                                    <br><span class="badge bg-secondary" title="En la app aparece como Anónimo">Anónimo en la app</span>
                                @endif
                            </td>
                            <td>{{ $fecha }}</td>
                            <td class="text-nowrap">
                                <button type="button" class="btn btn-info btn-sm text-white"
                                        data-bs-toggle="modal" data-bs-target="#modalComentario"
                                        data-contenido="{{ $item->contenido }}"
                                        data-autor="{{ $autorTexto }}"
                                        data-fecha="{{ $fecha }}"
                                        data-publicacion="{{ $pub->titulo ?? 'Publicación eliminada' }}"
                                        data-categoria="{{ $pub->categoria_foro->nombre ?? 'Sin categoría' }}"
                                        data-anonimo="{{ $item->anonimo_en_app ? 1 : 0 }}"
                                        data-oculta="{{ $pub && $pub->oculta ? 1 : 0 }}">
                                    Ver
                                </button>

                                <button type="button"
                                        class="btn btn-danger btn-sm"
                                        data-url="{{ route('comentario_foro.destroy', $item->id) }}"
                                        data-token="{{ csrf_token() }}"
                                        data-autor="{{ $item->autor->correo ?? 'usuario eliminado' }}"
                                        data-resumen="{{ \Illuminate\Support\Str::limit($item->contenido, 80) }}"
                                        onclick="destroy(this)">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="admin-empty">
                                No hay comentarios con esos filtros. Prueba ampliando el rango de fechas o limpiando la búsqueda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 d-flex justify-content-center">
            {{ $comentarios->links('pagination::bootstrap-5') }}
        </div>
    </div>

    {{-- Modal de detalle --}}
    <div class="modal fade admin-modal" id="modalComentario" tabindex="-1" aria-labelledby="modalComentarioTitulo" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalComentarioTitulo">Detalle del comentario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning d-none" data-campo="oculta" role="status">
                        La publicación de este comentario está oculta; los demás usuarios no la ven en la app.
                    </div>
                    <div class="contenido-completo mb-3" data-campo="contenido"></div>
                    <hr>
                    <p class="mb-1"><strong>Autor:</strong> <span data-campo="autor"></span>
                        <span class="badge bg-secondary d-none" data-campo="anonimo">Anónimo en la app</span>
                    </p>
                    <p class="mb-1"><strong>Fecha:</strong> <span data-campo="fecha"></span></p>
                    <p class="mb-0"><strong>Publicación:</strong> <span data-campo="publicacion"></span>
                        <span class="text-muted">(<span data-campo="categoria"></span>)</span>
                    </p>
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
    <script src="{{ asset('js/comentario_foro.js') }}"></script>
@endsection
