{{-- Campos compartidos por create y edit. $farmacia existe solo al editar. --}}
<div class="row g-3 admin-form">
    <div class="col-md-8">
        <label for="nombre">Nombre de la farmacia</label>
        <input type="text" id="nombre" name="nombre"
               class="form-control @error('nombre') is-invalid @enderror"
               maxlength="150" placeholder="Ej. Farmacia San Nicolás"
               value="{{ old('nombre', $farmacia->nombre ?? '') }}" required>
        @error('nombre')
        <span class="invalid-feedback d-block" role="alert">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="telefono">Teléfono</label>
        <input type="text" id="telefono" name="telefono"
               class="form-control @error('telefono') is-invalid @enderror"
               maxlength="20" placeholder="Ej. 2222-3333"
               value="{{ old('telefono', $farmacia->telefono ?? '') }}" required>
        @error('telefono')
        <span class="invalid-feedback d-block" role="alert">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
    </div>

    <div class="col-12">
        <label for="direccion">Dirección</label>
        <input type="text" id="direccion" name="direccion"
               class="form-control @error('direccion') is-invalid @enderror"
               maxlength="255" placeholder="Calle, colonia, municipio, departamento"
               value="{{ old('direccion', $farmacia->direccion ?? '') }}" required>
        @error('direccion')
        <span class="invalid-feedback d-block" role="alert">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="correo">Correo electrónico</label>
        <input type="email" id="correo" name="correo"
               class="form-control @error('correo') is-invalid @enderror"
               maxlength="150" placeholder="contacto@farmacia.com"
               value="{{ old('correo', $farmacia->correo ?? '') }}" required>
        @error('correo')
        <span class="invalid-feedback d-block" role="alert">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="latitud">Latitud</label>
        <input type="text" id="latitud" name="latitud" inputmode="decimal"
               class="form-control @error('latitud') is-invalid @enderror"
               placeholder="Ej. 13.6929000"
               value="{{ old('latitud', $farmacia->latitud ?? '') }}" required>
        <div class="form-text">Entre -90 y 90.</div>
        @error('latitud')
        <span class="invalid-feedback d-block" role="alert">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="longitud">Longitud</label>
        <input type="text" id="longitud" name="longitud" inputmode="decimal"
               class="form-control @error('longitud') is-invalid @enderror"
               placeholder="Ej. -89.2182000"
               value="{{ old('longitud', $farmacia->longitud ?? '') }}" required>
        <div class="form-text">Entre -180 y 180.</div>
        @error('longitud')
        <span class="invalid-feedback d-block" role="alert">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
    </div>

    <div class="col-12">
        <div class="form-text">
            Tip: en Google Maps, clic derecho sobre el lugar y copia las coordenadas (latitud, longitud).
        </div>
    </div>
</div>
