<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CentroSalud;

class CentroSaludController extends Controller
{
    /**
     * GET /centros_salud
     *   Sin parametros: todos los centros (comportamiento original).
     *
     * GET /centros_salud?lat=13.6989&lng=-89.1914
     *   Con coordenadas: agrega "distancia_km" (formula de Haversine) y
     *   devuelve la lista ordenada del MAS CERCANO al mas lejano.
     *
     * El calculo se hace en PHP (no en SQL) para que funcione igual en MySQL
     * y en los tests con SQLite. Solo se piden la latitud y longitud juntas.
     */
    public function index(Request $request)
    {
        $datos = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $lat = $datos['lat'] ?? null;
        $lng = $datos['lng'] ?? null;

        if (($lat === null) !== ($lng === null)) {
            return response()->json([
                'mensaje' => 'lat y lng deben enviarse juntos',
            ], 422);
        }

        $centros = CentroSalud::all();

        if ($lat !== null && $lng !== null) {
            $centros = $centros
                ->map(function (CentroSalud $c) use ($lat, $lng) {
                    $c->distancia_km = $this->haversine(
                        (float) $lat,
                        (float) $lng,
                        (float) $c->latitud,
                        (float) $c->longitud
                    );

                    return $c;
                })
                ->sortBy('distancia_km')
                ->values();
        }

        return response()->json($centros, 200);
    }

    /** Distancia en kilometros entre dos puntos (redondeada a 2 decimales). */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $radio = 6371.0; // radio de la Tierra en km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return round($radio * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }
}
