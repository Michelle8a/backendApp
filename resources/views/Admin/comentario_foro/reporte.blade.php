<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de comentarios del foro</title>
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
        .muted { color: #777; }
        .tag { background: #6c757d; color: #fff; padding: 1px 4px; font-size: 7.5px; }
        .tag-oculta { background: #d97706; color: #fff; padding: 1px 4px; font-size: 7.5px; }
        .aviso { margin-top: 8px; padding: 6px 8px; background: #fff3cd; border: 1px solid #ffe69c; font-size: 8.5px; }
    </style>
</head>
<body>
    <header>
        <h1>Reporte de comentarios del foro</h1>
        <p>
            Generado el {{ $generado }}@if($generadoPor) por {{ $generadoPor }}@endif
        </p>
    </header>

    <table class="resumen">
        <tr>
            <td><div class="num">{{ $resumen['total'] }}</div><div class="lbl">Comentarios</div></td>
            <td><div class="num">{{ $resumen['autores'] }}</div><div class="lbl">Autores distintos</div></td>
            <td><div class="num">{{ $resumen['publicaciones'] }}</div><div class="lbl">Publicaciones comentadas</div></td>
            <td><div class="num">{{ $resumen['en_ocultas'] }}</div><div class="lbl">En publicaciones ocultas</div></td>
        </tr>
    </table>

    <div class="filtros">
        @if (count($filtros))
            <b>Filtros aplicados:</b>
            @foreach ($filtros as $nombre => $valor)
                {{ $nombre }}: {{ $valor }}@if(!$loop->last) &nbsp;|&nbsp; @endif
            @endforeach
        @else
            <b>Filtros aplicados:</b> ninguno (todos los comentarios)
        @endif
    </div>

    <table class="datos">
        <thead>
            <tr>
                <th style="width: 5%;">Cód.</th>
                <th style="width: 42%;">Comentario</th>
                <th style="width: 21%;">Publicación</th>
                <th style="width: 22%;">Autor</th>
                <th style="width: 10%;">Fecha</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($comentarios as $c)
                <tr>
                    <td>{{ $c->id }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($c->contenido, 400) }}</td>
                    <td>
                        @if ($c->publicacion)
                            {{ $c->publicacion->titulo }}
                            <br><span class="muted">{{ $c->publicacion->categoria_foro->nombre ?? 'Sin categoría' }}</span>
                            @if ($c->publicacion->oculta) <span class="tag-oculta">Oculta</span>@endif
                        @else
                            <span class="muted">Publicación eliminada</span>
                        @endif
                    </td>
                    <td>
                        {{ $c->autor->correo ?? 'Usuario eliminado' }}
                        @if ($c->autor)<br><span class="muted">{{ $c->autor->rol }}@if($c->autor_nombre) · {{ $c->autor_nombre }}@endif</span>@endif
                        @if ($c->anonimo_en_app) <span class="tag">Anónimo en la app</span>@endif
                    </td>
                    <td>{{ \Illuminate\Support\Carbon::parse($c->creado_en)->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted" style="padding: 18px; text-align: center;">No hay comentarios que coincidan con los filtros.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($truncado)
        <div class="aviso">
            Se muestran los primeros {{ $comentarios->count() }} de {{ $resumen['total'] }} comentarios.
            Aplica filtros (fechas o publicación) para obtener el resto. Los totales del resumen incluyen todos.
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
