<?php

namespace Tests\Feature;

use App\Models\CentroSalud;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentroSaludTest extends TestCase
{
    use RefreshDatabase;

    private function centro(string $nombre, float $lat, float $lng): CentroSalud
    {
        return CentroSalud::create([
            'nombre'    => $nombre,
            'tipo'      => 'hospital',
            'direccion' => 'Direccion de prueba',
            'latitud'   => $lat,
            'longitud'  => $lng,
            'telefono'  => '2222-0000',
        ]);
    }

    public function test_es_publico_y_devuelve_todos(): void
    {
        $this->centro('Cerca', 13.7000, -89.2000);
        $this->centro('Lejos', 13.8000, -89.3000);

        $this->getJson('/api/centros_salud')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['nombre' => 'Cerca']);

        // sin coordenadas no se calcula distancia
        $this->getJson('/api/centros_salud')->assertOk()
            ->assertJsonMissing(['distancia_km']);
    }

    public function test_con_coordenadas_los_ordena_del_mas_cercano_al_mas_lejano(): void
    {
        $this->centro('En el punto', 13.7000, -89.2000);  // 0 km
        $this->centro('A 1 km', 13.7100, -89.2000);       // ~1.1 km
        $this->centro('A 11 km', 13.8000, -89.2000);      // ~11 km

        $r = $this->getJson('/api/centros_salud?lat=13.7000&lng=-89.2000')->assertOk();

        $r->assertJsonCount(3)
          ->assertJsonPath('0.nombre', 'En el punto')
          ->assertJsonPath('1.nombre', 'A 1 km')
          ->assertJsonPath('2.nombre', 'A 11 km');

        $this->assertSame(0.0, (float) $r->json('0.distancia_km'));
        $this->assertGreaterThan($r->json('0.distancia_km'), $r->json('1.distancia_km'));
        $this->assertGreaterThan($r->json('1.distancia_km'), $r->json('2.distancia_km'));
    }

    public function test_lat_o_lng_solas_no_se_aceptan(): void
    {
        $this->getJson('/api/centros_salud?lat=13.7')->assertStatus(422);
        $this->getJson('/api/centros_salud?lng=-89.2')->assertStatus(422);
    }

    public function test_coordenadas_fuera_de_rango_no_se_aceptan(): void
    {
        $this->getJson('/api/centros_salud?lat=200&lng=-89.2')->assertStatus(422);
        $this->getJson('/api/centros_salud?lat=13.7&lng=300')->assertStatus(422);
    }
}
