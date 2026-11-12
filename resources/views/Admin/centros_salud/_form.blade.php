{{-- Campos compartidos por create y edit. $centro existe solo al editar. $tipos viene del controller. --}}
<div class="row g-3 admin-form">
    <div class="col-md-8">
        <label for="nombre">Nombre del centro de salud</label>
        <input type="text" id="nombre" name="nombre"
               class="form-control @error('nombre') is-invalid @enderror"
               maxlength="150" placeholder="Ej. Hospital Nacional Rosales"
               value="{{ old('nombre', $centro->nombre ?? '') }}" required>
        @error('nombre')
        <span class="invalid-feedback d-block" role="alert">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="tipo">Tipo</label>
        <select id="tipo" name="tipo" class="form-select @error('tipo') is-invalid @enderror" required>
            <option value="">-- Seleccione un tipo --</option>
            @foreach ($tipos as $valor => $etiqueta)
                <option value="{{ $valor }}"
                    {{ old('tipo', $centro->tipo ?? '') === $valor ? 'selected' : '' }}>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>
        @error('tipo')
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
               value="{{ old('direccion', $centro->direccion ?? '') }}" required>
        @error('direccion')
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
               value="{{ old('telefono', $centro->telefono ?? '') }}" required>
        @error('telefono')
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
               value="{{ old('latitud', $centro->latitud ?? '') }}" required>
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
               value="{{ old('longitud', $centro->longitud ?? '') }}" required>
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
