<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Hacer home_goals/away_goals NULL (sin DBAL)
        // MySQL/MariaDB: MODIFY conserva UNSIGNED y tamaño
        DB::statement("ALTER TABLE `matches` MODIFY `home_goals` TINYINT UNSIGNED NULL");
        DB::statement("ALTER TABLE `matches` MODIFY `away_goals` TINYINT UNSIGNED NULL");

        // (Opcional pero útil) Normaliza datos antiguos: si el partido NO está 'played' y goles son 0, pásalos a NULL
        DB::table('matches')
            ->whereIn('status', ['scheduled','postponed','canceled'])
            ->where('home_goals', 0)->where('away_goals', 0)
            ->update(['home_goals' => null, 'away_goals' => null]);

        // 2) Índices para filtros habituales
        Schema::table('matches', function (Blueprint $table) {
            // Evita colisiones de nombre si ya existen (no pasa nada si no existían)
            try { $table->index(['league_id','status','scheduled_at'], 'idx_matches_league_status_date'); } catch (\Throwable $e) {}
            try { $table->index(['league_id','scheduled_at'], 'idx_matches_league_date'); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        // Revertir a NOT NULL con 0 (si hiciera falta)
        DB::statement("ALTER TABLE `matches` MODIFY `home_goals` TINYINT UNSIGNED NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE `matches` MODIFY `away_goals` TINYINT UNSIGNED NOT NULL DEFAULT 0");

        Schema::table('matches', function (Blueprint $table) {
            try { $table->dropIndex('idx_matches_league_status_date'); } catch (\Throwable $e) {}
            try { $table->dropIndex('idx_matches_league_date'); } catch (\Throwable $e) {}
        });
    }
};
