<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('standings', function (Blueprint $table) {
            // 1) Columna group_name (nullable por compatibilidad)
            if (!Schema::hasColumn('standings', 'group_name')) {
                $table->string('group_name', 50)->nullable()->after('league_id');
            }
        });

        // 2) Reemplaza la UNIQUE antigua (league_id, matchday_number)
        //    Nombre por convención suele ser: standings_league_id_matchday_number_unique
        try {
            DB::statement('ALTER TABLE `standings` DROP INDEX `standings_league_id_matchday_number_unique`');
        } catch (\Throwable $e) {
            // Si no existe con ese nombre, ignora
        }

        // 3) Nueva UNIQUE y un índice auxiliar
        Schema::table('standings', function (Blueprint $table) {
            try { $table->unique(['league_id','group_name','matchday_number'], 'uq_standings_league_group_day'); } catch (\Throwable $e) {}
            try { $table->index(['league_id','group_name'], 'idx_standings_league_group'); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('standings', function (Blueprint $table) {
            try { $table->dropIndex('idx_standings_league_group'); } catch (\Throwable $e) {}
            try { $table->dropUnique('uq_standings_league_group_day'); } catch (\Throwable $e) {}
            if (Schema::hasColumn('standings', 'group_name')) {
                $table->dropColumn('group_name');
            }
        });

        // Reponer la UNIQUE antigua (si la necesitas)
        try {
            DB::statement('ALTER TABLE `standings` ADD UNIQUE `standings_league_id_matchday_number_unique` (`league_id`,`matchday_number`)');
        } catch (\Throwable $e) {}
    }
};
