<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Farmacia;

class FarmaciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $farmacias = [
            [
                'nombre'    => 'Farmacias Economicas',
                'direccion' => 'San Salvador, San Salvador',
                'latitud'   => 13.6929000,
                'longitud'  => -89.2182000,
                'telefono'  => '2200-0001',
                'correo'    => 'contacto@economicas.com',
            ],
            [
                'nombre'    => 'Farmacia San Nicolas',
                'direccion' => 'Santa Tecla, La Libertad',
                'latitud'   => 13.6769000,
                'longitud'  => -89.2797000,
                'telefono'  => '2200-0002',
                'correo'    => 'contacto@sannicolas.com',
            ],
            [
                'nombre'    => 'Farmacias Uno',
                'direccion' => 'Soyapango, San Salvador',
                'latitud'   => 13.7100000,
                'longitud'  => -89.1390000,
                'telefono'  => '2200-0003',
                'correo'    => 'contacto@uno.com',
            ],
        ];

        foreach ($farmacias as $farmacia) {
            Farmacia::firstOrCreate(
                ['nombre' => $farmacia['nombre']],
                $farmacia
            );
        }
    }
}
