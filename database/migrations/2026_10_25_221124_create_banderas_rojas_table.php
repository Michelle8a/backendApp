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
        Schema::create('banderas_rojas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cita_id')->constrained('citas')->onDelete('cascade');
            $table->timestamp('creado_en')->useCurrent();

            //$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banderas_rojas');
    }
};
