<?php

namespace App\Console\Commands;

use App\Models\Citas;
use App\Models\Medico;
use App\Models\Notificacion;
use App\Models\Paciente;
use App\Services\Notificador;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EnviarRecordatoriosCitas extends Command
{
    protected $signature = 'citas:recordatorios';
    protected $description = 'Envia recordatorios de citas confirmadas (24 h y 1 h antes)';

    public function handle(): int
    {
        $ahora = now();

        $this->enviar($ahora->copy()->addHours(23), $ahora->copy()->addHours(24), '24h');
        $this->enviar($ahora->copy(), $ahora->copy()->addHour(), '1h');

        return self::SUCCESS;
    }

    private function enviar(Carbon $desde, Carbon $hasta, string $tipo): void
    {
        $citas = Citas::where('estado', 'confirmada')
            ->whereBetween('fecha_hora', [$desde, $hasta])
            ->get();

        foreach ($citas as $cita) {
            $fecha = Carbon::parse($cita->fecha_hora);
            // incluye la fecha: si la cita se reprograma, vuelve a avisar
            $clave = $tipo . '|' . $fecha->format('Y-m-d H:i:s');
            $cuando = $fecha->copy()->locale('es')->isoFormat('ddd D MMM, h:mm A');

            $usuarios = array_filter([
                Paciente::whereKey($cita->paciente_id)->value('usuario_id'),
                Medico::whereKey($cita->medico_id)->value('usuario_id'),
            ]);

            foreach ($usuarios as $uid) {
                $yaEnviado = Notificacion::where('usuario_id', $uid)
                    ->where('tipo', 'recordatorio_cita')
                    ->where('cita_id', $cita->id)
                    ->where('datos->clave', $clave)
                    ->exists();

                if ($yaEnviado) {
                    continue;
                }

                $titulo = $tipo === '1h' ? 'Tu cita es en una hora' : 'Recordatorio de cita';
                $cuerpo = $tipo === '1h'
                    ? "Tu cita empieza a las {$fecha->format('g:i A')}."
                    : "Tienes una cita el {$cuando}.";

                Notificador::notificar(
                    $uid,
                    'recordatorio_cita',
                    $titulo,
                    $cuerpo,
                    ['pantalla' => 'cita', 'cita_id' => $cita->id, 'clave' => $clave],
                    $cita->id
                );
            }
        }
    }
}