<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        .ds-navbar {
            background: #050f1e;
            border-bottom: 1px solid #1c3d66;
            padding: 12px 0;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }
        .ds-navbar .navbar-collapse,
        .ds-navbar .navbar-collapse.collapse { visibility: visible !important; }
        @media (min-width: 768px) {
            .ds-navbar .navbar-collapse { display: flex !important; flex-basis: auto; align-items: center; }
        }
        .ds-navbar .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            font-size: 19px;
            font-weight: 700;
            letter-spacing: -.3px;
        }
        .ds-navbar .navbar-brand:hover { color: #fff; }
        .ds-navbar .brand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            color: #4f9be0;
            background: rgba(79, 155, 224, .14);
            border: 1px solid rgba(79, 155, 224, .3);
            border-radius: 8px;
            font-size: 18px;
        }
        .ds-navbar .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            color: #b4c6da;
            font-size: 15px;
            font-weight: 500;
            border-radius: 8px;
            transition: background-color .2s ease, color .2s ease;
        }
        .ds-navbar .nav-link:hover,
        .ds-navbar .nav-link:focus { color: #fff; background: #0f2a4a; }
        .ds-navbar .nav-link.active { color: #fff; background: #0f2a4a; }
        .ds-navbar .ds-user {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 14px;
            color: #b4c6da;
            font-size: 14px;
            background: #0b2240;
            border: 1px solid #1c3d66;
            border-radius: 999px;
        }
        .ds-navbar .ds-user i { color: #4f9be0; font-size: 16px; }
        .ds-navbar .ds-user strong { color: #fff; font-weight: 600; }
        .ds-navbar .btn-ds-outline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 16px;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            background: transparent;
            border: 1px solid #1c3d66;
            border-radius: 8px;
            transition: background-color .2s ease, border-color .2s ease;
        }
        .ds-navbar .btn-ds-outline:hover { color: #fff; background: #0f2a4a; border-color: #4f9be0; }
        .ds-navbar .btn-ds-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            background: #2a7bc8;
            border: 1px solid #2a7bc8;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color .2s ease;
        }
        .ds-navbar .btn-ds-primary:hover { color: #fff; background: #3a8ad8; border-color: #3a8ad8; }
        .ds-navbar .navbar-toggler { border-color: #1c3d66; padding: 6px 10px; }
        .ds-navbar .navbar-toggler:focus { box-shadow: 0 0 0 2px #4f9be0; }
        .ds-navbar .ds-actions { gap: 10px; }
        @media (max-width: 767px) {
            .ds-navbar .navbar-collapse { margin-top: 14px; padding-top: 14px; border-top: 1px solid #1c3d66; }
            .ds-navbar .ds-actions { flex-direction: column; align-items: stretch !important; margin-top: 12px; }
            .ds-navbar .ds-user, .ds-navbar .btn-ds-outline, .ds-navbar .btn-ds-primary { justify-content: center; }
        }

        /* ---------- Menús desplegables ---------- */
        .ds-navbar .dropdown-toggle::after { margin-left: 4px; align-self: center; }
        .ds-navbar .dropdown-menu {
            min-width: 230px;
            padding: 6px;
            margin-top: 0;
            background: #0b2240;
            border: 1px solid #1c3d66;
            border-radius: 8px;
            box-shadow: 0 14px 30px rgba(0, 0, 0, .4);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }
        .ds-navbar .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            color: #b4c6da;
            font-size: 14px;
            font-weight: 500;
            border-radius: 6px;
        }
        .ds-navbar .dropdown-item i { color: #4f9be0; font-size: 16px; }
        .ds-navbar .dropdown-item:hover,
        .ds-navbar .dropdown-item:focus { color: #fff; background: #0f2a4a; }
        .ds-navbar .dropdown-item.active { color: #fff; background: #133459; }
        .ds-navbar .nav-link.dropdown-toggle.active { color: #fff; background: #0f2a4a; }
        @media (min-width: 768px) {
            /* se abren al pasar el mouse */
            .ds-navbar .dropdown-menu { top: 100%; left: 0; }
            .ds-navbar .dropdown:hover > .dropdown-menu { display: block; }
            .ds-navbar .dropdown:hover > .nav-link { color: #fff; background: #0f2a4a; }
        }
        @media (max-width: 767px) {
            .ds-navbar .dropdown-menu { min-width: 0; padding: 0 0 0 14px; background: transparent; border: 0; box-shadow: none; }
        }
    </style>

    @yield('styles')

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app">

        <nav class="navbar navbar-expand-md navbar-dark ds-navbar sticky-top">
            <div class="container">

                <a class="navbar-brand" href="{{ route('home') }}">
                    <span class="brand-icon"><i class="bi bi-heart-pulse-fill"></i></span>
                    Hi! Doctor
                </a>

                <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarDocstor"
                    aria-controls="navbarDocstor"
                    aria-expanded="false"
                    aria-label="Abrir menú">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarDocstor">

                    <ul class="navbar-nav me-auto mt-3 mt-md-0">
                        @auth('web')
                            @php
                                $personas  = request()->routeIs('medico.*', 'admin.pacientes.*', 'admin.usuarios.*');
                                $catalogos = request()->routeIs('admin.idiomas.*', 'admin.alergias.*', 'admin.enfermedades.*', 'admin.especialidades.*', 'admin.centros_salud.*', 'admin.farmacias.*');
                                $foro      = request()->routeIs('categoria_foro.*', 'publicacion_foro.*', 'comentario_foro.*');
                                $actividad = request()->routeIs('citas.*', 'bitacora.*');
                            @endphp

                            {{-- PERSONAS --}}
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ $personas ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-people"></i> Personas
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item {{ request()->routeIs('medico.*') ? 'active' : '' }}" href="{{ route('medico.index') }}"><i class="bi bi-person-vcard"></i> Médicos</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('admin.pacientes.*') ? 'active' : '' }}" href="{{ route('admin.pacientes.index') }}"><i class="bi bi-person-heart"></i> Pacientes</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}" href="{{ route('admin.usuarios.index') }}"><i class="bi bi-person-gear"></i> Usuarios</a></li>
                                </ul>
                            </li>

                            {{-- CATÁLOGOS --}}
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ $catalogos ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-collection"></i> Catálogos
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item {{ request()->routeIs('admin.idiomas.*') ? 'active' : '' }}" href="{{ route('admin.idiomas.index') }}"><i class="bi bi-globe"></i> Idiomas</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('admin.alergias.*') ? 'active' : '' }}" href="{{ route('admin.alergias.index') }}"><i class="bi bi-shield-exclamation"></i> Alergias</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('admin.enfermedades.*') ? 'active' : '' }}" href="{{ route('admin.enfermedades.index') }}"><i class="bi bi-clipboard2-pulse"></i> Enfermedades</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('admin.especialidades.*') ? 'active' : '' }}" href="{{ route('admin.especialidades.index') }}"><i class="bi bi-award"></i> Especialidades</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('admin.centros_salud.*') ? 'active' : '' }}" href="{{ route('admin.centros_salud.index') }}"><i class="bi bi-hospital"></i> Centros de salud</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('admin.farmacias.*') ? 'active' : '' }}" href="{{ route('admin.farmacias.index') }}"><i class="bi bi-shop"></i> Farmacias</a></li>
                                </ul>
                            </li>

                            {{-- FORO --}}
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ $foro ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-chat-left-text"></i> Foro
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item {{ request()->routeIs('categoria_foro.*') ? 'active' : '' }}" href="{{ route('categoria_foro.show') }}"><i class="bi bi-tags"></i> Categorías</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('publicacion_foro.*') ? 'active' : '' }}" href="{{ route('publicacion_foro.show') }}"><i class="bi bi-journal-richtext"></i> Publicaciones</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('comentario_foro.*') ? 'active' : '' }}" href="{{ route('comentario_foro.show') }}"><i class="bi bi-chat-dots"></i> Comentarios</a></li>
                                </ul>
                            </li>

                            {{-- ACTIVIDAD --}}
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ $actividad ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-activity"></i> Actividad
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item {{ request()->routeIs('citas.*') ? 'active' : '' }}" href="{{ route('citas.index') }}"><i class="bi bi-calendar-check"></i> Citas</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('bitacora.*') ? 'active' : '' }}" href="{{ route('bitacora.index') }}"><i class="bi bi-journal-text"></i> Bitácora</a></li>
                                </ul>
                            </li>
                        @endauth
                    </ul>

                    <div class="d-flex align-items-md-center ds-actions">
                        @if(Auth::guard('web')->check())

                            <span class="ds-user">
                                <i class="bi bi-person-circle"></i>
                                <span>Bienvenido, <strong>{{ explode('@', Auth::guard('web')->user()->correo)[0] }}</strong></span>
                            </span>

                            <form action="{{ route('logout') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="btn-ds-outline w-100">
                                    <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                                </button>
                            </form>

                        @else

                            <a class="btn-ds-primary" href="{{ route('login') }}">
                                <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
                            </a>

                        @endif
                    </div>

                </div>
            </div>
        </nav>

        {{-- Las páginas con @section('full-width', '1') ocupan todo el ancho --}}
        @hasSection('full-width')
            <main>
                @yield('content')
            </main>
        @else
            <main class="container py-4">
                @yield('content')
            </main>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>