<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; color: #1a2b2c; font-size: 12px; }
  .encabezado { border-bottom: 3px solid #2F6F73; padding-bottom: 10px; margin-bottom: 18px; }
  .titulo { font-size: 22px; font-weight: bold; color: #2F6F73; }
  .sub { color: #6b7a7b; font-size: 11px; }
  .caja { background: #f4f1ea; border-radius: 6px; padding: 10px 12px; margin-bottom: 14px; }
  .caja b { color: #2F6F73; }
  table { width: 100%; border-collapse: collapse; margin-top: 6px; }
  th { background: #2F6F73; color: #fff; text-align: left; padding: 7px; font-size: 11px; }
  td { padding: 7px; border-bottom: 1px solid #ddd5c6; vertical-align: top; }
  .pie { margin-top: 30px; font-size: 9px; color: #6b7a7b; word-break: break-all; }
  .estado { float: right; font-size: 11px; text-transform: uppercase; color: #2F6F73; font-weight: bold; }
</style>
</head>
<body>
  <div class="encabezado">
    <span class="estado">{{ $receta->estado }}</span>
    <div class="titulo">Receta médica</div>
    <div class="sub">Código: {{ $receta->codigo }} &nbsp;·&nbsp; Fecha: {{ $fecha }}</div>
  </div>

  <div class="caja">
    <b>Paciente:</b> {{ $receta->paciente->nombres }} {{ $receta->paciente->apellidos }}<br>
    <b>Médico:</b> {{ $receta->medico->nombres }} {{ $receta->medico->apellidos }}
  </div>

  <table>
    <thead>
      <tr>
        <th>Medicamento</th>
        <th>Dosis</th>
        <th>Frecuencia</th>
        <th>Duración</th>
        <th>Indicaciones</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($receta->detalles as $d)
        <tr>
          <td><b>{{ $d->medicamento }}</b></td>
          <td>{{ $d->dosis }}</td>
          <td>{{ $d->frecuencia }}</td>
          <td>{{ $d->duracion }}</td>
          <td>{{ $d->indicaciones }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="pie">
    Firma digital: {{ $receta->firma_digital }}<br>
    Documento generado por la aplicación de servicios médicos a distancia.
  </div>
</body>
</html>