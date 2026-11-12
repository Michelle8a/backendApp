<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Alergias</title>
    <style>
        @page { margin: 40px 40px 60px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }

        .banner { width: 100%; background: #060E69; color: #fff; border-collapse: collapse; }
        .banner td { padding: 16px 18px; }
        .banner .titulo { font-size: 20px; font-weight: bold; }
        .banner .sub { font-size: 10px; margin-top: 3px; }
        .banner .derecha { text-align: right; font-size: 10px; }

        .resumen { width: 100%; margin: 14px 0; border-collapse: collapse; }
        .resumen td { padding: 6px 10px; background: #e8eaf6; border: 1px solid #c5cae9; font-size: 10px; }
        .resumen strong { color: #060E69; }

        table.datos { width: 100%; border-collapse: collapse; }
        table.datos thead th {
            background: #060E69; color: #fff; padding: 8px; font-size: 10px;
            text-transform: uppercase; border: 1px solid #060E69;
        }
        table.datos tbody td { padding: 7px 8px; border: 1px solid #d5d8ee; }
        table.datos tbody tr:nth-child(even) td { background: #f5f6fc; }
        table.datos tfoot td {
            padding: 7px 8px; border: 1px solid #c5cae9; background: #e8eaf6;
            font-weight: bold; color: #060E69;
        }
        .centro { text-align: center; }
        .vacio { text-align: center; color: #777; padding: 18px; }

        .footer {
            position: fixed; bottom: -35px; left: 0; right: 0;
            font-size: 9px; color: #666; border-top: 1px solid #ccc; padding-top: 5px;
        }
        .pagenum:before { content: counter(page); }
    </style>
</head>
<body>

    <div class="footer">
        Reporte de Alergias · Generado el {{ $fecha }} · Página <span class="pagenum"></span>
    </div>

    <table class="banner">
        <tr>
            <td>
                <div class="titulo">Reporte de Alergias</div>
                <div class="sub">Catálogo de alergias y pacientes que las registran</div>
            </td>
            <td class="derecha">
                Fecha: {{ $fecha }}<br>
                Generado por: {{ $generadoPor }}
            </td>
        </tr>
    </table>

    <table class="resumen">
        <tr>
            <td><strong>Total de alergias:</strong> {{ $alergias->count() }}</td>
            <td><strong>Filtro aplicado:</strong> {{ $q !== '' ? '"' . $q . '"' : 'Ninguno (todos los registros)' }}</td>
        </tr>
    </table>

    <table class="datos">
        <thead>
            <tr>
                <th style="width: 8%">ID</th>
                <th>Nombre</th>
                <th style="width: 13%">Pacientes</th>
                <th style="width: 11%">Leve</th>
                <th style="width: 13%">Moderada</th>
                <th style="width: 11%">Grave</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($alergias as $item)
                <tr>
                    <td class="centro">{{ $item->id }}</td>
                    <td>{{ $item->nombre }}</td>
                    <td class="centro">{{ $item->pacientes_count }}</td>
                    <td class="centro">{{ $item->leves_count }}</td>
                    <td class="centro">{{ $item->moderadas_count }}</td>
                    <td class="centro">{{ $item->graves_count }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="vacio">No hay alergias que coincidan con el filtro.</td>
                </tr>
            @endforelse
        </tbody>
        @if ($alergias->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align: right">Totales</td>
                    <td class="centro">{{ $alergias->sum('pacientes_count') }}</td>
                    <td class="centro">{{ $alergias->sum('leves_count') }}</td>
                    <td class="centro">{{ $alergias->sum('moderadas_count') }}</td>
                    <td class="centro">{{ $alergias->sum('graves_count') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

</body>
</html>
