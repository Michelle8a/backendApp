@if (auth()->check() && auth()->user()->rol === 'admin')
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button"
           data-bs-toggle="dropdown" aria-expanded="false">
            Catálogos
        </a>
        <ul class="dropdown-menu">
            <li>
                <a class="dropdown-item {{ request()->routeIs('admin.idiomas.*') ? 'active' : '' }}"
                   href="{{ route('admin.idiomas.index') }}">Idiomas</a>
            </li>
            <li>
                <a class="dropdown-item {{ request()->routeIs('admin.alergias.*') ? 'active' : '' }}"
                   href="{{ route('admin.alergias.index') }}">Alergias</a>
            </li>
            <li>
                <a class="dropdown-item {{ request()->routeIs('admin.enfermedades.*') ? 'active' : '' }}"
                   href="{{ route('admin.enfermedades.index') }}">Enfermedades</a>
            </li>
            <li>
                <a class="dropdown-item {{ request()->routeIs('admin.especialidades.*') ? 'active' : '' }}"
                   href="{{ route('admin.especialidades.index') }}">Especialidades</a>
            </li>
            <li>
                <a class="dropdown-item {{ request()->routeIs('admin.centros_salud.*') ? 'active' : '' }}"
                   href="{{ route('admin.centros_salud.index') }}">Centros de salud</a>
            </li>
            <li>
                <a class="dropdown-item {{ request()->routeIs('admin.farmacias.*') ? 'active' : '' }}"
                   href="{{ route('admin.farmacias.index') }}">Farmacias</a>
            </li>
        </ul>
    </li>
@endif
