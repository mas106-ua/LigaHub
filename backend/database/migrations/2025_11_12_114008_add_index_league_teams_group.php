<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('league_teams', function (Blueprint $table) {
            // (league_id, group_name, team_id) — útil para comprobar pertenencia y filtrar
            try { $table->index(['league_id','group_name','team_id'], 'idx_leagueTeams_league_group_team'); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('league_teams', function (Blueprint $table) {
            try { $table->dropIndex('idx_leagueTeams_league_group_team'); } catch (\Throwable $e) {}
        });
    }
};
