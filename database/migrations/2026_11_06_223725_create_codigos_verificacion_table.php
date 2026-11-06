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
        Schema::create('codigos_verificacion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');

            $table->string('codigo_hash');
            $table->timestamp('expira_en');
            $table->unsignedTinyInteger('intentos')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codigos_verificacion');
    }
};
