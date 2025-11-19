<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('match_events', function (Blueprint $table) {
            $table->foreignId('related_player_id')
                ->nullable()
                ->after('player_id')
                ->constrained('players')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('match_events', function (Blueprint $table) {
            $table->dropForeign(['related_player_id']);
            $table->dropColumn('related_player_id');
        });
    }
};
