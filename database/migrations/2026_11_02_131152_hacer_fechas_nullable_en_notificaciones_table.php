<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dateTime('programada_para')->nullable()->change();
            $table->dateTime('enviada_en')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dateTime('programada_para')->nullable(false)->change();
            $table->dateTime('enviada_en')->nullable(false)->change();
        });
    }
};
