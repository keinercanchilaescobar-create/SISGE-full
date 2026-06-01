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
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('tipo', 50);
            $table->string('marca', 50);
            $table->string('modelo', 50);
            $table->enum('estado', [
                'Disponible',
                'Prestado',
                'En Mantenimiento',
                'Fuera de Servicio',
                'No Disponible',
            ])->default('Disponible');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};