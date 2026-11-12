<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reporte de médicos</title>
<style>
    @page { margin: 90px 30px 50px 30px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
    header { position: fixed; top: -70px; left: 0; right: 0; }
    .banda { background: #060E69; color: #fff; padding: 10px 14px; }
    .banda h1 { margin: 0; font-size: 17px; }
    .banda span { font-size: 9px; color: #c9ceff; }
    .meta { margin: 6px 0 10px; font-size: 9px; color: #555; }
    table.datos { width: 100%; border-collapse: collapse; }
    table.datos th { background: #060E69; color: #fff; padding: 6px 5px; text-align: left; font-size: 9px; }
    table.datos td { border-bottom: 1px solid #d6d9ea; padding: 5px; vertical-align: top; }
    table.datos tr:nth-child(even) td { background: #f4f5fb; }
    .ok { color: #137333; font-weight: bold; }
    .no { color: #b06000; font-weight: bold; }
    .resumen { margin-top: 12px; font-size: 10px; }
</style>
</head>
<body>
<header>
    <div class="banda">
        <h1>Reporte de médicos</h1>
        <span>Generado el {{ $generado }} por {{ $admin }}</span>
    </div>
</header>

<div class="meta">
    <strong>Filtros aplicados:</strong> {{ count($filtros) ? implode(' · ', $filtros) : 'Ninguno (todos los médicos)' }}
</div>

<table class="datos">
    <thead>
        <tr>
            <th>#</th>
            <th>Médico</th>
            <th>JVPM</th>
            <th>Correo</th>
            <th>Centro de salud</th>
            <th>Especialidades</th>
            <th>Idiomas</th>
            <th>Tarifa</th>
            <th>Calif.</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($medicos as $m)
            <tr>
                <td>{{ $m->id }}</td>
                <td>{{ $m->apellidos }}, {{ $m->nombres }}</td>
                <td>{{ $m->jvpm }}</td>
                <td>{{ $m->usuario->correo ?? '—' }}</td>
                <td>{{ $m->centro_salud->nombre ?? '—' }}</td>
                <td>
                    {{ $m->es_general ? 'Médico general' : '' }}
                    {{ $m->especialidades->pluck('nombre')->implode(', ') }}
                </td>
                <td>{{ $m->idiomas->pluck('nombre')->implode(', ') ?: '—' }}</td>
                <td>${{ number_format($m->tarifa, 2) }}</td>
                <td>{{ number_format($m->calificacion_promedio, 2) }}</td>
                <td class="{{ $m->verificado ? 'ok' : 'no' }}">{{ $m->verificado ? 'Verificado' : 'Sin verificar' }}</td>
            </tr>
        @empty
            <tr><td colspan="10" style="text-align:center; padding:14px;">No hay médicos con los filtros aplicados.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="resumen">
    <strong>Total:</strong> {{ $medicos->count() }} médicos
    · <strong>Verificados:</strong> {{ $medicos->where('verificado', true)->count() }}
    · <strong>Sin verificar:</strong> {{ $medicos->where('verificado', false)->count() }}
</div>

<script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $pdf->page_text($pdf->get_width() - 100, $pdf->get_height() - 30, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 8, [0.35, 0.35, 0.35]);
    }
</script>
</body>
</html>
