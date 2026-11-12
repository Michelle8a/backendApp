@extends('layouts.app')

@section('title', 'Iniciar sesión | Hi ! Doctor')
@section('full-width', '1')

@section('content')
<style>
    body { background: #050f1e; }

    .ds-auth {
        display: flex;
        align-items: center;
        min-height: calc(100vh - 66px);
        padding: 56px 0;
        background: linear-gradient(180deg, #050f1e 0%, #071a31 100%);
        font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    }
    .ds-auth-card {
        width: 100%;
        max-width: 440px;
        margin: 0 auto;
        padding: 36px;
        background: #0b2240;
        border: 1px solid #1c3d66;
        border-radius: 12px;
        box-shadow: 0 24px 60px rgba(0, 0, 0, .35);
    }
    .ds-auth-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 52px;
        height: 52px;
        margin-bottom: 20px;
        color: #4f9be0;
        font-size: 26px;
        background: rgba(79, 155, 224, .14);
        border: 1px solid rgba(79, 155, 224, .3);
        border-radius: 10px;
    }
    .ds-auth-card h1 { margin: 0 0 6px; color: #fff; font-size: 26px; font-weight: 700; letter-spacing: -.4px; }
    .ds-auth-sub { margin: 0 0 26px; color: #8aa2bd; font-size: 15px; line-height: 1.6; }

    .ds-auth-card label { margin-bottom: 6px; color: #b4c6da; font-size: 14px; font-weight: 600; }
    .ds-auth-card .form-control {
        padding: 11px 14px;
        color: #fff;
        background: #081a30;
        border: 1px solid #1c3d66;
        border-radius: 8px;
    }
    .ds-auth-card .form-control::placeholder { color: #6b829c; }
    .ds-auth-card .form-control:focus {
        color: #fff;
        background: #081a30;
        border-color: #4f9be0;
        box-shadow: 0 0 0 .2rem rgba(79, 155, 224, .25);
    }
    .ds-auth-card .form-control:-webkit-autofill {
        -webkit-text-fill-color: #fff;
        box-shadow: 0 0 0 1000px #081a30 inset;
    }
    .ds-auth-card .form-control.is-invalid { border-color: #e07a7a; background-image: none; }
    .ds-auth-card .invalid-feedback { color: #f0a3a3; font-size: 13px; }

    .ds-pass { position: relative; }
    .ds-pass .form-control { padding-right: 46px; }
    .ds-pass button {
        position: absolute;
        top: 50%;
        right: 8px;
        transform: translateY(-50%);
        padding: 6px 8px;
        color: #8aa2bd;
        font-size: 17px;
        background: transparent;
        border: 0;
        border-radius: 6px;
    }
    .ds-pass button:hover { color: #fff; }

    .ds-auth-card .form-check-input { background-color: #081a30; border-color: #1c3d66; }
    .ds-auth-card .form-check-input:checked { background-color: #2a7bc8; border-color: #2a7bc8; }
    .ds-auth-card .form-check-label { color: #b4c6da; font-size: 14px; font-weight: 400; }

    .ds-auth-btn {
        width: 100%;
        padding: 12px;
        color: #fff;
        font-size: 15px;
        font-weight: 600;
        background: #2a7bc8;
        border: 1px solid #2a7bc8;
        border-radius: 8px;
        transition: background-color .2s ease;
    }
    .ds-auth-btn:hover { background: #3a8ad8; border-color: #3a8ad8; }
    .ds-auth-link { display: block; margin-top: 18px; color: #8aa2bd; font-size: 14px; text-align: center; text-decoration: none; }
    .ds-auth-link:hover { color: #fff; }

    .ds-auth-alert {
        display: flex;
        gap: 12px;
        margin-bottom: 22px;
        padding: 14px 16px;
        color: #f4c4c4;
        font-size: 14px;
        line-height: 1.55;
        background: rgba(196, 61, 61, .14);
        border: 1px solid rgba(224, 122, 122, .45);
        border-radius: 8px;
    }
    .ds-auth-alert i { color: #f08a8a; font-size: 18px; }
    .ds-auth-alert strong { display: block; color: #fff; font-weight: 600; }

    @media (max-width: 575px) {
        .ds-auth { padding: 32px 0; }
        .ds-auth-card { padding: 26px; }
    }
</style>

<section class="ds-auth">
    <div class="container">
        <div class="ds-auth-card">
            <span class="ds-auth-icon"><i class="bi bi-heart-pulse-fill"></i></span>
            <h1>Iniciar sesión</h1>
            <p class="ds-auth-sub">Accede al panel de administración de Hi! Doctor.</p>

            {{-- Sin permisos (usuario válido pero no administrador) --}}
            @error('permiso')
                <div class="ds-auth-alert" role="alert">
                    <i class="bi bi-shield-lock-fill"></i>
                    <div>
                        <strong>Acceso denegado</strong>
                        {{ $message }}
                    </div>
                </div>
            @enderror

            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="correo">Correo electrónico</label>
                    <input id="correo" type="email" name="correo"
                           class="form-control @error('correo') is-invalid @enderror"
                           value="{{ old('correo') }}" placeholder="nombre@correo.com"
                           required autocomplete="username" autofocus>
                    @error('correo')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password">Contraseña</label>
                    <div class="ds-pass">
                        <input id="password" type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Tu contraseña" required autocomplete="current-password">
                        <button type="button" id="togglePassword" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>


                <button type="submit" class="ds-auth-btn">Ingresar</button>
            </form>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const mostrar = input.type === 'password';
        input.type = mostrar ? 'text' : 'password';
        this.innerHTML = '<i class="bi bi-' + (mostrar ? 'eye-slash' : 'eye') + '"></i>';
        this.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
</script>
@endsection