<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La columna se añade solo si no existe: la migración base de `usuarios`
        // ya crea `correo_verificado_en`, así que en una instalación limpia
        // intentar añadirla de nuevo rompía `migrate` (columna duplicada).
        if (! Schema::hasColumn('codigos_verificacion', 'usado_en')) {
            Schema::table('codigos_verificacion', function (Blueprint $table) {
                $table->timestamp('usado_en')->nullable()->after('intentos');
            });
        }

        if (! Schema::hasColumn('usuarios', 'correo_verificado_en')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->timestamp('correo_verificado_en')->nullable()->after('estado');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('codigos_verificacion', 'usado_en')) {
            Schema::table('codigos_verificacion', function (Blueprint $table) {
                $table->dropColumn('usado_en');
            });
        }

        // `correo_verificado_en` pertenece a la migración base de `usuarios`,
        // no a esta: no se elimina aquí para no romper el rollback.
    }
};
