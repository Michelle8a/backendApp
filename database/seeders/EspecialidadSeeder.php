<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Especialidad;

class EspecialidadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $especialidades = [
            ['nombre' => 'Medicina General', 'descripcion' => 'Atencion primaria, diagnostico y tratamiento de enfermedades'],
            ['nombre' => 'Psicologia', 'descripcion' => 'Evaluacion y terapia de la salud mental']
        ];

        foreach ($especialidades as $especialidad) {
            Especialidad::firstOrCreate(
                ['nombre' => $especialidad['nombre']],
                ['descripcion' => $especialidad['descripcion']]
            );
        }
    }
}
