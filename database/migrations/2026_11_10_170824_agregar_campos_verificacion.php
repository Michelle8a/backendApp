<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('codigos_verificacion', function (Blueprint $table) {
            $table->timestamp('usado_en')->nullable()->after('intentos');
        });

        Schema::table('usuarios', function (Blueprint $table) {
            $table->timestamp('correo_verificado_en')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('codigos_verificacion', function (Blueprint $table) {
            $table->dropColumn('usado_en');
        });

        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('correo_verificado_en');
        });
    }
};