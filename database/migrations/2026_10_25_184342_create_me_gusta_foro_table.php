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
        Schema::create('me_gusta_foro', function (Blueprint $table) {
            
            $table->foreignId('publicacion_id')->constrained('publicaciones_foro')->onDelete('cascade');
            $table->foreignId('autor_id')->constrained('usuarios')->onDelete('cascade');

            $table->primary(['publicacion_id', 'autor_id']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('me_gusta_foro');
    }
};
