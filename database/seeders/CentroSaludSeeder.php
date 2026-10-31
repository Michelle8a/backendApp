<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CentroSalud;

class CentroSaludSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $centros = [
            [
                'nombre'    => 'Hospital Central San Salvador',
                'tipo'      => 'hospital',
                'direccion' => 'Colonia Escalon, San Salvador',
                'latitud'   => 13.6989000,
                'longitud'  => -89.2370000,
                'telefono'  => '2222-0001',
            ],
            [
                'nombre'    => 'Clinica Santa Tecla',
                'tipo'      => 'clinica',
                'direccion' => 'Centro de Santa Tecla, La Libertad',
                'latitud'   => 13.6769000,
                'longitud'  => -89.2797000,
                'telefono'  => '2222-0002',
            ],
            [
                'nombre'    => 'Laboratorio Clinico',
                'tipo'      => 'laboratorio',
                'direccion' => 'Colonia Medica, San Salvador',
                'latitud'   => 13.7013000,
                'longitud'  => -89.2150000,
                'telefono'  => '2222-0003',
            ],
        ];

        foreach ($centros as $centro) {
            CentroSalud::firstOrCreate(
                ['nombre' => $centro['nombre']], 
                $centro                          
            );
        }
    }
}
