<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medicos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->foreignId('centro_salud_id')->constrained('centros_salud')->onDelete('cascade');

            $table->string('jvpm', 100);
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->boolean('es_general')->default(false);
            $table->text('biografia')->nullable();
            $table->decimal('tarifa', 8, 2)->default(0.00);
            $table->decimal('calificacion_promedio', 3, 2)->default(0.00);
            $table->boolean('verificado')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicos');
    }
};
