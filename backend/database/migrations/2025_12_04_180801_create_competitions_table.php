<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();

            // Nombre y código/slug único de la competición
            $table->string('name', 150);
            $table->string('code', 50)->unique();

            // Relación con categoría (Senior, Juvenil Nacional, etc.)
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            // Atributos estables de la competición
            $table->enum('level', ['pro', 'semi', 'amateur']);
            $table->enum('gender', ['mixed', 'male', 'female'])->default('mixed');

            // Ámbito geográfico
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete();

            // Tipo de competición (oficial / privada)
            $table->enum('type', ['official', 'private'])->default('official');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índices para filtros
            $table->index('category_id');
            $table->index('level');
            $table->index('gender');
            $table->index('region_id');
            $table->index('province_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }

};
