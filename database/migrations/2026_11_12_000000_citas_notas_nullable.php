<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // CitasController@store guarda notas_medico_cifradas = null al crear la cita,
    // pero la migracion original la creo NOT NULL: POST /citas fallaba con error 500.
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->text('notas_medico_cifradas')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->text('notas_medico_cifradas')->nullable(false)->change();
        });
    }
};