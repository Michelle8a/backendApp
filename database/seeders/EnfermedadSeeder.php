<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Enfermedad;

class EnfermedadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $enfermedades = ['Dolor de Cabeza', 'Cancer', 'Gastritis'];

        foreach ($enfermedades as $nombre) {

            //busca en la bd, sino existe lo crea
            Enfermedad::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
