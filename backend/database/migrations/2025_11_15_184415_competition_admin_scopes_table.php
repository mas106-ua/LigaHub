<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_admin_scopes', function (Blueprint $table) {
            $table->id();

            // Usuario que tiene el permiso
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Nivel de categoría: pro / semi / amateur
            // NULL = cualquier nivel
            $table->enum('level', ['pro','semi','amateur'])->nullable();

            // CCAA (region)
            // NULL = todas las CCAA
            $table->foreignId('region_id')
                ->nullable()
                ->constrained('regions')
                ->nullOnDelete();

            $table->timestamps();

            // Evitar duplicados por usuario + combinación
            $table->unique(['user_id', 'level', 'region_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_admin_scopes');
    }
};
