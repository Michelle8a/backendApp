@extends('layouts.app')

@section('title', 'Acceso denegado | DocStor')
@section('full-width', '1')

@section('content')
<style>
    body { background: #050f1e; }
    .ds-403 {
        display: flex;
        align-items: center;
        min-height: calc(100vh - 66px);
        padding: 56px 0;
        background: linear-gradient(180deg, #050f1e 0%, #071a31 100%);
        font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    }
    .ds-403-card {
        max-width: 480px;
        margin: 0 auto;
        padding: 40px 36px;
        text-align: center;
        background: #0b2240;
        border: 1px solid #1c3d66;
        border-radius: 12px;
        box-shadow: 0 24px 60px rgba(0, 0, 0, .35);
    }
    .ds-403-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 64px;
        height: 64px;
        margin-bottom: 20px;
        color: #f08a8a;
        font-size: 30px;
        background: rgba(196, 61, 61, .14);
        border: 1px solid rgba(224, 122, 122, .45);
        border-radius: 12px;
    }
    .ds-403-card h1 { margin: 0 0 10px; color: #fff; font-size: 26px; font-weight: 700; letter-spacing: -.4px; }
    .ds-403-card p { margin: 0 0 26px; color: #b4c6da; line-height: 1.7; }
    .ds-403-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 22px;
        color: #fff;
        font-weight: 600;
        text-decoration: none;
        background: #2a7bc8;
        border-radius: 8px;
        transition: background-color .2s ease;
    }
    .ds-403-btn:hover { color: #fff; background: #3a8ad8; }
</style>

<section class="ds-403">
    <div class="container">
        <div class="ds-403-card">
            <span class="ds-403-icon"><i class="bi bi-shield-lock-fill"></i></span>
            <h1>Acceso denegado</h1>
            <p>{{ $exception->getMessage() ?: 'No tienes permisos autorizados para acceder a esta sección.' }}</p>
            <a href="{{ route('home') }}" class="ds-403-btn"><i class="bi bi-house"></i> Volver al inicio</a>
        </div>
    </div>
</section>
@endsection