<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de publicaciones del foro</title>
    <style>
        @page { margin: 90px 30px 50px 30px; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { font-size: 9px; color: #222; }

        header { position: fixed; top: -75px; left: 0; right: 0; height: 60px;
                 background: #060E69; color: #fff; padding: 12px 18px; }
        header h1 { margin: 0 0 3px; font-size: 16px; color: #fff; }
        header p  { margin: 0; font-size: 9px; color: #d5d8f3; }

        .resumen { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 10px; }
        .resumen td { background: #eceefb; border: 1px solid #dfe2f0; padding: 7px 10px; width: 25%; }
        .resumen .num { font-size: 15px; font-weight: bold; color: #060E69; }
        .resumen .lbl { font-size: 8px; color: #555; }

        .filtros { margin-bottom: 8px; font-size: 9px; color: #444; }
        .filtros b { color: #060E69; }

        table.datos { width: 100%; border-collapse: collapse; }
        table.datos th { background: #060E69; color: #fff; padding: 6px 5px; text-align: left; font-size: 8.5px; }
        table.datos td { border: 1px solid #dfe2f0; padding: 5px; vertical-align: top; }
        table.datos tr:nth-child(even) td { background: #f7f8fd; }
        table.datos tr.oculta td { background: #efeff2; color: #666; }
        .c { text-align: center; }
        .muted { color: #777; }
        .tag { background: #6c757d; color: #fff; padding: 1px 4px; font-size: 7.5px; }
        .tag-oculta { background: #d97706; color: #fff; padding: 1px 4px; font-size: 7.5px; }
        .tag-visible { background: #198754; color: #fff; padding: 1px 4px; font-size: 7.5px; }
        .aviso { margin-top: 8px; padding: 6px 8px; background: #fff3cd; border: 1px solid #ffe69c; font-size: 8.5px; }
    </style>
</head>
<body>
    <header>
        <h1>Reporte de publicaciones del foro</h1>
        <p>
            Generado el {{ $generado }}@if($generadoPor) por {{ $generadoPor }}@endif
        </p>
    </header>

    <table class="resumen">
        <tr>
            <td><div class="num">{{ $resumen['total'] }}</div><div class="lbl">Publicaciones</div></td>
            <td><div class="num">{{ $resumen['visibles'] }}</div><div class="lbl">Visibles</div></td>
            <td><div class="num">{{ $resumen['ocultas'] }}</div><div class="lbl">Ocultas</div></td>
            <td><div class="num">{{ $resumen['anonimas'] }}</div><div class="lbl">Anónimas</div></td>
        </tr>
    </table>

    <div class="filtros">
        @if (count($filtros))
            <b>Filtros aplicados:</b>
            @foreach ($filtros as $nombre => $valor)
                {{ $nombre }}: {{ $valor }}@if(!$loop->last) &nbsp;|&nbsp; @endif
            @endforeach
        @else
            <b>Filtros aplicados:</b> ninguno (todas las publicaciones)
        @endif
    </div>

    <table class="datos">
        <thead>
            <tr>
                <th style="width: 4%;">Cód.</th>
                <th style="width: 13%;">Título</th>
                <th style="width: 10%;">Categoría</th>
                <th style="width: 14%;">Autor</th>
                <th style="width: 8%;">Fecha</th>
                <th style="width: 32%;">Contenido</th>
                <th style="width: 9%;">Estado</th>
                <th style="width: 5%;" class="c">Coment.</th>
                <th style="width: 5%;" class="c">Me gusta</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($publicaciones as $p)
                <tr class="{{ $p->oculta ? 'oculta' : '' }}">
                    <td>{{ $p->id }}</td>
                    <td>{{ $p->titulo }}</td>
                    <td>{{ $p->categoria_foro->nombre ?? 'Sin categoría' }}</td>
                    <td>
                        {{ $p->usuario->correo ?? 'Usuario eliminado' }}
                        @if ($p->usuario)<br><span class="muted">{{ $p->usuario->rol }}</span>@endif
                        @if ($p->anonimo) <span class="tag">Anónima</span>@endif
                    </td>
                    <td>{{ \Illuminate\Support\Carbon::parse($p->creado_en)->format('d/m/Y H:i') }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($p->contenido, 280) }}</td>
                    <td>
                        @if ($p->oculta)
                            <span class="tag-oculta">Oculta</span>
                            <br><span class="muted">{{ optional($p->oculta_en)->format('d/m/Y H:i') }}</span>
                            @if ($p->moderador)<br><span class="muted">{{ $p->moderador->correo }}</span>@endif
                        @else
                            <span class="tag-visible">Visible</span>
                        @endif
                    </td>
                    <td class="c">{{ $p->comentarios_count }}</td>
                    <td class="c">{{ $p->me_gusta_count }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="c muted" style="padding: 18px;">No hay publicaciones que coincidan con los filtros.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($truncado)
        <div class="aviso">
            Se muestran las primeras {{ $publicaciones->count() }} de {{ $resumen['total'] }} publicaciones.
            Aplica filtros (fechas o categoría) para obtener el resto. Los totales del resumen incluyen todas.
        </div>
    @endif

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
            $pdf->page_text(730, 568, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 8, [0.4, 0.4, 0.4]);
        }
    </script>
</body>
</html>
