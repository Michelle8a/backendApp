<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de usuarios</title>
    <style>
        @page { margin: 28px 30px 40px 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #222; }
        .encabezado { background: #060E69; color: #fff; padding: 14px 18px; }
        .encabezado h1 { margin: 0 0 3px; font-size: 18px; }
        .encabezado p { margin: 0; font-size: 9px; color: #d5d8f0; }
        .meta { margin: 10px 0; font-size: 9px; color: #444; }
        .meta strong { color: #060E69; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        th { background: #060E69; color: #fff; padding: 6px 5px; text-align: left; font-size: 9px; }
        td { padding: 5px; border-bottom: 1px solid #dfe2ee; }
        tr { page-break-inside: avoid; }
        tbody tr:nth-child(even) td { background: #f4f5fb; }
        .activo { color: #146c43; font-weight: bold; }
        .inactivo { color: #b02a37; font-weight: bold; }
        .resumen { margin-top: 10px; font-size: 10px; font-weight: bold; color: #060E69; }
        .vacio { text-align: center; padding: 20px; color: #777; }
    </style>
</head>
<body>
    <div class="encabezado">
        <h1>Reporte de usuarios</h1>
        <p>Generado el {{ $generado }} por {{ $admin }}</p>
    </div>

    <div class="meta">
        <strong>Filtros aplicados:</strong>
        @if (count($filtros))
            @foreach ($filtros as $nombre => $valor)
                {{ $nombre }}: {{ $valor }}{{ ! $loop->last ? ' | ' : '' }}
            @endforeach
        @else
            ninguno (todos los usuarios)
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Cód.</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Rol</th>
                <th>Correo verificado</th>
                <th>Estado</th>
                <th>Registro</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($usuarios as $u)
                <tr>
                    <td>{{ $u->id }}</td>
                    <td>{{ $u->correo }}</td>
                    <td>{{ $u->telefono }}</td>
                    <td>{{ $roles[$u->rol] ?? $u->rol }}</td>
                    <td>{{ $u->correo_verificado_en ? 'Verificado' : 'Pendiente' }}</td>
                    <td class="{{ $u->estado === 'activo' ? 'activo' : 'inactivo' }}">
                        {{ $estados[$u->estado] ?? $u->estado }}
                    </td>
                    <td>{{ $u->created_at?->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="vacio">No hay usuarios con los filtros indicados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="resumen">
        Total: {{ $usuarios->count() }}
        | Activos: {{ $usuarios->where('estado', 'activo')->count() }}
        | Desactivados: {{ $usuarios->where('estado', 'suspendido')->count() }}
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $pdf->page_text(480, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 8, [0.4, 0.4, 0.4]);
        }
    </script>
</body>
</html>
