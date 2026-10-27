<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Alergia;

class AlergiaSeeder extends Seeder
{
    
    public function run(): void
    {
        $alergias = ['Pelo de Gato', 'Mani', 'Amoxicilina'];


        foreach ($alergias as $nombre) {
            Alergia::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
