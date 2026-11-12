@extends('layouts.app')
@section('title', 'Categorías del foro')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <div class="admin-header">
        <h1>Categorías del foro</h1>
        <p>Administra los temas en los que los usuarios pueden publicar.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="admin-card">
        <div class="admin-toolbar">
            <a class="btn btn-admin btn-sm" href="{{ route('categoria_foro.create') }}">
                Agregar nueva categoría
            </a>

            <input type="search" id="buscar" class="form-control form-control-sm search-box"
                   placeholder="Buscar categoría" aria-label="Buscar categoría">
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-bordered table-admin" id="tabla-categorias">
                <thead>
                    <tr>
                        <th style="width: 90px;">Código</th>
                        <th>Nombre</th>
                        <th style="width: 150px;">Publicaciones</th>
                        <th style="width: 190px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categorias as $item)
                        <tr class="fila" data-nombre="{{ mb_strtolower($item->nombre) }}">
                            <td>{{ $item->id }}</td>
                            <td>{{ $item->nombre }}</td>
                            <td><span class="badge badge-admin">{{ $item->publicaciones_count }}</span></td>
                            <td>
                                <a class="btn btn-warning btn-sm"
                                   href="{{ route('categoria_foro.edit', $item->id) }}">
                                    Modificar
                                </a>

                                <button type="button"
                                        class="btn btn-danger btn-sm"
                                        data-url="{{ route('categoria_foro.destroy', $item->id) }}"
                                        data-token="{{ csrf_token() }}"
                                        data-nombre="{{ $item->nombre }}"
                                        onclick="destroy(this)">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="admin-empty">
                                Aún no hay categorías. Agrega la primera para que los usuarios puedan publicar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="sin-resultados" class="admin-empty d-none">
            Ninguna categoría coincide con tu búsqueda.
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/categoria_foro.js') }}"></script>
@endsection
