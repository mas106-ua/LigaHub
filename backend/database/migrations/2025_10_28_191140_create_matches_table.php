<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained('leagues')->cascadeOnDelete();
            $table->unsignedSmallInteger('matchday_number');
            $table->foreignId('home_team_id')->constrained('teams')->restrictOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->restrictOnDelete();
            $table->dateTime('scheduled_at')->nullable();
            $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->enum('status', ['scheduled','played','postponed','canceled'])->default('scheduled');
            $table->unsignedTinyInteger('home_goals')->default(0);
            $table->unsignedTinyInteger('away_goals')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['league_id','matchday_number','home_team_id','away_team_id'],
                'uq_matches_league_day_home_away'
            );            
            $table->index(['league_id','matchday_number'], 'idx_matches_league_day');
        });
    }
    public function down(): void {
        Schema::dropIfExists('matches');
    }
};
