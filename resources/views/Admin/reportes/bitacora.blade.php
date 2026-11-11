<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de bitácora</title>
    <style>
        @page { margin: 28px 30px 40px 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #222; }
        .encabezado { background: #060E69; color: #fff; padding: 14px 18px; }
        .encabezado h1 { margin: 0 0 3px; font-size: 18px; }
        .encabezado p { margin: 0; font-size: 9px; color: #d5d8f0; }
        .meta { margin: 10px 0; font-size: 9px; color: #444; }
        .meta strong { color: #060E69; }
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
        <h1>Reporte de bitácora de auditoría</h1>
        <p>Generado el {{ $generado }} por {{ $admin }}</p>
    </div>

    <div class="meta">
        <strong>Filtros aplicados:</strong>
        @if (count($filtros))
            @foreach ($filtros as $nombre => $valor)
                {{ $nombre }}: {{ $valor }}{{ ! $loop->last ? ' | ' : '' }}
            @endforeach
        @else
            ninguno (todos los registros)
        @endif
    </div>

    @if ($total > $limite)
        <div class="aviso">
            Hay {{ $total }} registros con estos filtros; este reporte incluye los {{ $limite }} más recientes.
            Acota el rango de fechas para obtener el resto.
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Cód.</th>
                <th>Fecha y hora</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Acción</th>
                <th>Recurso</th>
                <th>ID recurso</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($registros as $r)
                <tr>
                    <td>{{ $r->id }}</td>
                    <td>{{ $r->fecha?->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $r->usuario->correo ?? 'Usuario #' . $r->usuario_id }}</td>
                    <td>{{ ucfirst($r->usuario->rol ?? '—') }}</td>
                    <td>{{ ucfirst($r->accion) }}</td>
                    <td>{{ ucfirst($r->recurso) }}</td>
                    <td>{{ $r->recurso_id }}</td>
                    <td>{{ $r->ip }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="vacio">No hay registros con los filtros indicados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="total">Registros en este reporte: {{ $registros->count() }}</div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $pdf->page_text(720, 570, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 8, [0.4, 0.4, 0.4]);
        }
    </script>
</body>
</html>
