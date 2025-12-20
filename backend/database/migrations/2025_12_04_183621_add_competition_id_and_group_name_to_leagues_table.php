<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            // Nueva FK opcional a competitions
            $table->foreignId('competition_id')
                ->nullable()
                ->after('id')    // o donde te encaje mejor
                ->constrained('competitions')
                ->nullOnDelete();

            // Nombre de grupo dentro de la competición (Grupo 1, Grupo A, etc.)
            $table->string('group_name', 100)
                ->nullable()
                ->after('name');

            // 👇 IMPORTANTE:
            // De momento NO eliminamos aún campos antiguos (level, gender, region_id...)
            // para no romper nada del Sprint 2. Los dejaremos mientras hacemos
            // el refactor de endpoints (BE-REF-03/04) y seeders.
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropForeign(['competition_id']);
            $table->dropColumn(['competition_id', 'group_name']);
        });
    }
};

