{{-- Campos compartidos por crear y editar. $usuario es null al crear. --}}
@php
    $esCrear = ! isset($usuario) || $usuario === null;
    $rolBloqueado = $rolBloqueado ?? false;
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label mb-1" for="correo">Correo</label>
        <input type="email" id="correo" name="correo" class="form-control @error('correo') is-invalid @enderror"
               value="{{ old('correo', $usuario->correo ?? '') }}" maxlength="150" required>
        @error('correo')
            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label mb-1" for="telefono">Teléfono</label>
        <input type="text" id="telefono" name="telefono" class="form-control @error('telefono') is-invalid @enderror"
               value="{{ old('telefono', $usuario->telefono ?? '') }}" maxlength="20" required>
        @error('telefono')
            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label mb-1" for="rol">Rol</label>
        <select id="rol" name="rol" class="form-select @error('rol') is-invalid @enderror"
                {{ $rolBloqueado ? 'disabled' : 'required' }}>
            @if ($esCrear)
                <option value="">-- Seleccione un rol --</option>
            @endif
            @foreach ($roles as $val => $txt)
                <option value="{{ $val }}"
                    {{ old('rol', $usuario->rol ?? '') === $val ? 'selected' : '' }}>{{ $txt }}</option>
            @endforeach
        </select>
        @if ($rolBloqueado && ! empty($motivoBloqueo))
            <div class="form-text">{{ $motivoBloqueo }}</div>
        @endif
        @error('rol')
            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>

    @if ($esCrear)
        <div class="col-md-6"></div>

        <div class="col-md-6">
            <label class="form-label mb-1" for="contrasena">Contraseña inicial</label>
            <input type="password" id="contrasena" name="contrasena"
                   class="form-control @error('contrasena') is-invalid @enderror"
                   minlength="8" autocomplete="new-password" required>
            @error('contrasena')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
            <div class="form-text">Mínimo 8 caracteres.</div>
        </div>

        <div class="col-md-6">
            <label class="form-label mb-1" for="contrasena_confirmation">Confirmar contraseña</label>
            <input type="password" id="contrasena_confirmation" name="contrasena_confirmation"
                   class="form-control" minlength="8" autocomplete="new-password" required>
        </div>
    @endif
</div>
