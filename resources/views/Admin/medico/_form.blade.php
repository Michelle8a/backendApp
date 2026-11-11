{{-- Campos compartidos por crear y editar. $medico existe solo al editar. --}}
@php
    $editando = isset($medico);
    $espSel = old('especialidades', $editando ? $medico->especialidades->pluck('id')->all() : []);
    $idiSel = old('idiomas', $editando ? $medico->idiomas->pluck('id')->all() : []);
@endphp

<h5 class="titulo-seccion">Cuenta de acceso</h5>
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Correo electrónico</label>
        @if ($editando)
            <input type="email" class="form-control" value="{{ $medico->usuario->correo ?? '' }}" disabled>
        @else
            <input type="email" name="correo" class="form-control" value="{{ old('correo') }}" maxlength="120">
            @include('admin.partials.error', ['campo' => 'correo'])
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label">Teléfono</label>
        <input type="text" name="telefono" class="form-control" maxlength="20"
               value="{{ old('telefono', $editando ? ($medico->usuario->telefono ?? '') : '') }}">
        @include('admin.partials.error', ['campo' => 'telefono'])
    </div>
    @unless ($editando)
        <div class="col-md-6">
            <label class="form-label">Contraseña (mínimo 8 caracteres)</label>
            <input type="password" name="contrasena" class="form-control" maxlength="64" autocomplete="new-password">
            @include('admin.partials.error', ['campo' => 'contrasena'])
        </div>
        <div class="col-md-6">
            <label class="form-label">Confirmar contraseña</label>
            <input type="password" name="contrasena_confirmation" class="form-control" maxlength="64" autocomplete="new-password">
        </div>
    @endunless
</div>

<h5 class="titulo-seccion">Datos profesionales</h5>
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nombres</label>
        <input type="text" name="nombres" class="form-control" maxlength="100"
               value="{{ old('nombres', $medico->nombres ?? '') }}">
        @include('admin.partials.error', ['campo' => 'nombres'])
    </div>
    <div class="col-md-6">
        <label class="form-label">Apellidos</label>
        <input type="text" name="apellidos" class="form-control" maxlength="100"
               value="{{ old('apellidos', $medico->apellidos ?? '') }}">
        @include('admin.partials.error', ['campo' => 'apellidos'])
    </div>
    <div class="col-md-4">
        <label class="form-label">JVPM</label>
        <input type="text" name="jvpm" class="form-control" maxlength="100"
               value="{{ old('jvpm', $medico->jvpm ?? '') }}">
        @include('admin.partials.error', ['campo' => 'jvpm'])
    </div>
    <div class="col-md-5">
        <label class="form-label">Centro de salud</label>
        <select name="centro_salud_id" class="form-select">
            <option value="">-- Seleccione un centro --</option>
            @foreach ($centros as $c)
                <option value="{{ $c->id }}"
                    {{ old('centro_salud_id', $medico->centro_salud_id ?? '') == $c->id ? 'selected' : '' }}>
                    {{ $c->nombre }}
                </option>
            @endforeach
        </select>
        @include('admin.partials.error', ['campo' => 'centro_salud_id'])
    </div>
    <div class="col-md-3">
        <label class="form-label">Tarifa por consulta ($)</label>
        <input type="number" step="0.01" min="0" max="999999.99" name="tarifa" class="form-control"
               value="{{ old('tarifa', $medico->tarifa ?? '0.00') }}">
        @include('admin.partials.error', ['campo' => 'tarifa'])
    </div>
    <div class="col-md-6">
        <label class="form-label">Tipo de médico</label>
        <select name="es_general" class="form-select">
            <option value="0" {{ (string) old('es_general', isset($medico) ? (int) $medico->es_general : 0) === '0' ? 'selected' : '' }}>Especialista</option>
            <option value="1" {{ (string) old('es_general', isset($medico) ? (int) $medico->es_general : 0) === '1' ? 'selected' : '' }}>Médico general</option>
        </select>
        @include('admin.partials.error', ['campo' => 'es_general'])
    </div>
    <div class="col-md-6">
        <label class="form-label">Estado de verificación</label>
        <select name="verificado" class="form-select">
            <option value="1" {{ (string) old('verificado', isset($medico) ? (int) $medico->verificado : 1) === '1' ? 'selected' : '' }}>Verificado (visible en la app)</option>
            <option value="0" {{ (string) old('verificado', isset($medico) ? (int) $medico->verificado : 1) === '0' ? 'selected' : '' }}>Sin verificar (oculto en la app)</option>
        </select>
        @include('admin.partials.error', ['campo' => 'verificado'])
    </div>
    <div class="col-12">
        <label class="form-label">Biografía <small class="text-muted fw-normal">(máx. 200 caracteres)</small></label>
        <textarea name="biografia" class="form-control" rows="3" maxlength="200">{{ old('biografia', $medico->biografia ?? '') }}</textarea>
        @include('admin.partials.error', ['campo' => 'biografia'])
    </div>
</div>

<h5 class="titulo-seccion">Especialidades e idiomas</h5>
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Especialidades</label>
        <div class="lista-check">
            @forelse ($especialidades as $e)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="especialidades[]"
                           id="esp{{ $e->id }}" value="{{ $e->id }}" {{ in_array($e->id, $espSel) ? 'checked' : '' }}>
                    <label class="form-check-label" for="esp{{ $e->id }}">{{ $e->nombre }}</label>
                </div>
            @empty
                <span class="text-muted">Aún no hay especialidades registradas.</span>
            @endforelse
        </div>
        @include('admin.partials.error', ['campo' => 'especialidades'])
    </div>
    <div class="col-md-6">
        <label class="form-label">Idiomas</label>
        <div class="lista-check">
            @forelse ($idiomas as $i)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="idiomas[]"
                           id="idi{{ $i->id }}" value="{{ $i->id }}" {{ in_array($i->id, $idiSel) ? 'checked' : '' }}>
                    <label class="form-check-label" for="idi{{ $i->id }}">{{ $i->nombre }}</label>
                </div>
            @empty
                <span class="text-muted">Aún no hay idiomas registrados.</span>
            @endforelse
        </div>
        @include('admin.partials.error', ['campo' => 'idiomas'])
    </div>
</div>
