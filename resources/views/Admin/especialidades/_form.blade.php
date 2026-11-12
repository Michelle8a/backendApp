{{-- Campos compartidos por create y edit. $especialidad existe solo al editar. --}}
<div class="row g-3 admin-form">
    <div class="col-12">
        <label for="nombre">Nombre de la especialidad</label>
        <input type="text" id="nombre" name="nombre"
               class="form-control @error('nombre') is-invalid @enderror"
               maxlength="100" placeholder="Ej. Cardiología"
               value="{{ old('nombre', $especialidad->nombre ?? '') }}" required>
        <div class="form-text">Máximo 100 caracteres. No puede repetirse.</div>
        @error('nombre')
        <span class="invalid-feedback d-block" role="alert">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
    </div>

    <div class="col-12">
        <label for="descripcion">Descripción <span class="text-muted fw-normal">(opcional)</span></label>
        <textarea id="descripcion" name="descripcion" rows="3" maxlength="255"
                  class="form-control @error('descripcion') is-invalid @enderror"
                  placeholder="Breve descripción de la especialidad">{{ old('descripcion', $especialidad->descripcion ?? '') }}</textarea>
        <div class="form-text">Máximo 255 caracteres.</div>
        @error('descripcion')
        <span class="invalid-feedback d-block" role="alert">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
    </div>
</div>
