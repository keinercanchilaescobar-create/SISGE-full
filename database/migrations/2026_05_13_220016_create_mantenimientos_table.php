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
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')
                ->constrained('equipos')
                ->onDelete('cascade');
            $table->enum('tipo_mantenimiento', [
                'Preventivo',
                'Correctivo',
                'Actualización',
                'Calibración',
                'Otro',
            ]);
            $table->text('descripcion');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->enum('estado', [
                'En Proceso',
                'Completado',
                'Cancelado',
            ])->default('En Proceso');
            $table->string('tecnico_responsable', 100)->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
    }
};