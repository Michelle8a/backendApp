{{-- Campos compartidos por create y edit. $alergia existe solo al editar. --}}
<div class="row g-3 admin-form">
    <div class="col-12">
        <label for="nombre">Nombre de la alergia</label>
        <input type="text" id="nombre" name="nombre"
               class="form-control @error('nombre') is-invalid @enderror"
               maxlength="100" placeholder="Ej. Penicilina"
               value="{{ old('nombre', $alergia->nombre ?? '') }}" required>
        <div class="form-text">Máximo 100 caracteres. No puede repetirse.</div>
        @error('nombre')
        <span class="invalid-feedback d-block" role="alert">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
    </div>
</div>
