<?php

namespace App\Services;

use App\Models\Citas;
use App\Models\Medico;
use Carbon\Carbon;

/**
 * Calcula las horas libres de un medico cruzando:
 *   - sus horarios semanales (horarios_medico: dia_semana 1=lunes ... 7=domingo)
 *   - las citas ya tomadas (del medico y, si se indica, del paciente)
 *
 * Todo se calcula en la zona horaria de la app (config('app.timezone')), que debe ser
 * America/El_Salvador para que los horarios (hora de reloj local) tengan sentido.
 */
class DisponibilidadService
{
    public const DURACION_DEFAULT = 30;      // minutos
    private const ANTICIPACION_MIN = 30;     // no se ofrecen horas que empiecen en menos de 30 min

    /**
     * @return array<int, array{fecha:string, horas:string[]}>  solo los dias que tienen horas libres
     */
    public function horasLibres(
        Medico $medico,
        Carbon $desde,
        int $dias = 14,
        int $duracion = self::DURACION_DEFAULT,
        ?int $pacienteId = null
    ): array {
        $tz     = config('app.timezone');
        $desde  = $desde->copy()->setTimezone($tz)->startOfDay();
        $hasta  = $desde->copy()->addDays($dias);                 // exclusivo
        $limite = Carbon::now($tz)->addMinutes(self::ANTICIPACION_MIN);

        $horarios = $medico->horarios()->get()->groupBy('dia_semana');
        if ($horarios->isEmpty()) {
            return [];
        }

        // Citas activas del medico (y del paciente) en el rango, cargadas una sola vez
        $ocupadas = Citas::query()
            ->where(function ($q) use ($medico, $pacienteId) {
                $q->where('medico_id', $medico->id);
                if ($pacienteId) {
                    $q->orWhere('paciente_id', $pacienteId);
                }
            })
            ->whereNotIn('estado', ['cancelada', 'finalizada'])
            ->where('fecha_hora', '>=', $desde->copy()->subDay())
            ->where('fecha_hora', '<', $hasta)
            ->get(['fecha_hora', 'duracion_min'])
            ->map(function ($c) use ($tz) {
                $ini = Carbon::parse($c->fecha_hora, $tz);
                return [$ini, $ini->copy()->addMinutes((int) $c->duracion_min)];
            });

        $resultado = [];

        for ($i = 0; $i < $dias; $i++) {
            $dia     = $desde->copy()->addDays($i);
            $bloques = $horarios->get($dia->dayOfWeekIso);
            if (! $bloques) {
                continue;
            }

            $horas = [];
            foreach ($bloques as $b) {
                $ini = Carbon::parse($dia->toDateString() . ' ' . $b->hora_inicio, $tz);
                $fin = Carbon::parse($dia->toDateString() . ' ' . $b->hora_fin, $tz);

                for ($s = $ini->copy(); $s->copy()->addMinutes($duracion) <= $fin; $s->addMinutes($duracion)) {
                    if ($s <= $limite) {
                        continue;
                    }
                    $e = $s->copy()->addMinutes($duracion);

                    $choca = $ocupadas->contains(fn ($o) => $o[0] < $e && $o[1] > $s);
                    if (! $choca) {
                        $horas[] = $s->format('H:i');
                    }
                }
            }

            if ($horas) {
                sort($horas);
                $resultado[] = ['fecha' => $dia->toDateString(), 'horas' => array_values(array_unique($horas))];
            }
        }

        return $resultado;
    }

    /** True si ese inicio exacto sigue libre (dentro de un horario del medico y sin choques). */
    public function estaLibre(Medico $medico, Carbon $inicio, int $duracion, ?int $pacienteId = null): bool
    {
        $dias = $this->horasLibres($medico, $inicio->copy()->startOfDay(), 1, $duracion, $pacienteId);

        return ! empty($dias) && in_array($inicio->format('H:i'), $dias[0]['horas'], true);
    }
}