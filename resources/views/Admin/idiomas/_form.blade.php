{{-- Campos compartidos por create y edit. $idioma existe solo al editar. --}}
<div class="row g-3 admin-form">
    <div class="col-md-8">
        <label for="nombre">Nombre del idioma</label>
        <input type="text" id="nombre" name="nombre"
               class="form-control @error('nombre') is-invalid @enderror"
               maxlength="50" placeholder="Ej. Español"
               value="{{ old('nombre', $idioma->nombre ?? '') }}" required>
        @error('nombre')
        <span class="invalid-feedback d-block" role="alert">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="codigo">Código</label>
        <input type="text" id="codigo" name="codigo"
               class="form-control @error('codigo') is-invalid @enderror"
               maxlength="5" placeholder="Ej. es"
               value="{{ old('codigo', $idioma->codigo ?? '') }}" required>
        <div class="form-text">Máximo 5 caracteres (es, en, es-GT).</div>
        @error('codigo')
        <span class="invalid-feedback d-block" role="alert">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
    </div>
</div>
