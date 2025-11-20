<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('league_player_stat_overrides', function (Blueprint $table) {
            $table->id();

            $table->foreignId('league_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('player_id')
                ->constrained()
                ->cascadeOnDelete();

            // Deltas manuales que se suman a lo calculado por eventos
            $table->integer('goals_delta')->default(0);
            $table->integer('assists_delta')->default(0);
            $table->integer('yellow_cards_delta')->default(0);
            $table->integer('red_cards_delta')->default(0);

            $table->string('reason', 255)->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['league_id', 'player_id'], 'league_player_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_player_stat_overrides');
    }
};
