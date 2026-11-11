<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de citas</title>
    <style>
        @page { margin: 28px 30px 40px 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #222; }
        .encabezado { background: #060E69; color: #fff; padding: 14px 18px; }
        .encabezado h1 { margin: 0 0 3px; font-size: 18px; }
        .encabezado p { margin: 0; font-size: 9px; color: #d5d8f0; }
        .meta { margin: 10px 0 6px; font-size: 9px; color: #444; }
        .meta strong { color: #060E69; }
        .resumen { margin: 0 0 10px; padding: 6px 8px; background: #e9ebf7; color: #060E69; font-size: 9px; font-weight: bold; }
        .aviso { margin: 6px 0 10px; padding: 6px 8px; background: #fff3cd; color: #664d03; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        th { background: #060E69; color: #fff; padding: 6px 5px; text-align: left; font-size: 9px; }
        td { padding: 5px; border-bottom: 1px solid #dfe2ee; }
        tr { page-break-inside: avoid; }
        tbody tr:nth-child(even) td { background: #f4f5fb; }
        .total { margin-top: 10px; font-size: 10px; font-weight: bold; color: #060E69; }
        .vacio { text-align: center; padding: 20px; color: #777; }
    </style>
</head>
<body>
    <div class="encabezado">
        <h1>Reporte de citas</h1>
        <p>Generado el {{ $generado }} por {{ $admin }}</p>
    </div>

    <div class="meta">
        <strong>Filtros aplicados:</strong>
        @if (count($filtros))
            @foreach ($filtros as $nombre => $valor)
                {{ $nombre }}: {{ $valor }}{{ ! $loop->last ? ' | ' : '' }}
            @endforeach
        @else
            ninguno (todas las citas)
        @endif
    </div>

    <div class="resumen">
        Total: {{ $total }}
        @foreach ($estados as $val => $txt)
            | {{ $txt }}: {{ $resumen[$val] }}
        @endforeach
    </div>

    @if ($total > $limite)
        <div class="aviso">
            Hay {{ $total }} citas con estos filtros; este reporte incluye las {{ $limite }} más recientes.
            Acota el rango de fechas para obtener el resto.
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Cód.</th>
                <th>Fecha y hora</th>
                <th>Paciente</th>
                <th>Médico</th>
                <th>Tipo</th>
                <th>Duración</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($citas as $c)
                <tr>
                    <td>{{ $c->id }}</td>
                    <td>{{ \Carbon\Carbon::parse($c->fecha_hora)->format('d/m/Y H:i') }}</td>
                    <td>{{ $c->paciente ? $c->paciente->nombres . ' ' . $c->paciente->apellidos : '—' }}</td>
                    <td>{{ $c->medico ? $c->medico->nombres . ' ' . $c->medico->apellidos : '—' }}</td>
                    <td>{{ $tipos[$c->tipo] ?? $c->tipo }}</td>
                    <td>{{ $c->duracion_min }} min</td>
                    <td>{{ $estados[$c->estado] ?? $c->estado }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="vacio">No hay citas con los filtros indicados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="total">Citas en este reporte: {{ $citas->count() }}</div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $pdf->page_text(720, 570, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 8, [0.4, 0.4, 0.4]);
        }
    </script>
</body>
</html>
