<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Idioma;

class IdiomaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $idiomas = [
            ['nombre' => 'Español', 'codigo' => 'es'],
            ['nombre' => 'Ingles',  'codigo' => 'en'],
        ];

        foreach ($idiomas as $idioma) {
            Idioma::firstOrCreate(
                ['nombre' => $idioma['nombre']],   // con esto busca
                ['codigo' => $idioma['codigo']]    // esto se agrega si lo crea
            );
        }
    }
}
