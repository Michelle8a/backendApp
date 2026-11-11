{{-- Campos compartidos por create y edit. $enfermedad existe solo al editar. --}}
<div class="row g-3 admin-form">
    <div class="col-12">
        <label for="nombre">Nombre de la enfermedad</label>
        <input type="text" id="nombre" name="nombre"
               class="form-control @error('nombre') is-invalid @enderror"
               maxlength="120" placeholder="Ej. Diabetes mellitus tipo 2"
               value="{{ old('nombre', $enfermedad->nombre ?? '') }}" required>
        <div class="form-text">Máximo 120 caracteres. No puede repetirse.</div>
        @error('nombre')
        <span class="invalid-feedback d-block" role="alert">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
    </div>
</div>
