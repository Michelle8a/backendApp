<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de pacientes</title>
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
        .centro { text-align: center; }
        .total { margin-top: 10px; font-size: 10px; font-weight: bold; color: #060E69; }
        .vacio { text-align: center; padding: 20px; color: #777; }
    </style>
</head>
<body>
    <div class="encabezado">
        <h1>Reporte de pacientes</h1>
        <p>Generado el {{ $generado }} por {{ $admin }}</p>
    </div>

    <div class="meta">
        <strong>Filtros aplicados:</strong>
        @if (count($filtros))
            @foreach ($filtros as $nombre => $valor)
                {{ $nombre }}: {{ $valor }}{{ ! $loop->last ? ' | ' : '' }}
            @endforeach
        @else
            ninguno (todos los pacientes)
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Cód.</th>
                <th>Paciente</th>
                <th>Edad</th>
                <th>Sexo</th>
                <th>Sangre</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Idioma</th>
                <th>Dirección</th>
                <th class="centro">Citas</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pacientes as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td>{{ $p->nombres }} {{ $p->apellidos }}</td>
                    <td>{{ \Carbon\Carbon::parse($p->fecha_nacimiento)->age }}</td>
                    <td>{{ $p->sexo }}</td>
                    <td>{{ $p->tipo_sangre }}</td>
                    <td>{{ $p->usuario->correo ?? '—' }}</td>
                    <td>{{ $p->usuario->telefono ?? '—' }}</td>
                    <td>{{ $p->idioma->nombre ?? '—' }}</td>
                    <td>{{ $p->direccion }}</td>
                    <td class="centro">{{ $p->citas_count }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="vacio">No hay pacientes con los filtros indicados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="total">Total de pacientes: {{ $pacientes->count() }}</div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $pdf->page_text(720, 570, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 8, [0.4, 0.4, 0.4]);
        }
    </script>
</body>
</html>
