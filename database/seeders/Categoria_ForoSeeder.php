<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Categoria_Foro;

class Categoria_ForoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            'Salud general',
            'Nutricion y alimentacion',
            'Salud mental',
            'Salud infantil',
            'Salud de la mujer',
        ];

        foreach ($categorias as $nombre) {
            Categoria_Foro::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
