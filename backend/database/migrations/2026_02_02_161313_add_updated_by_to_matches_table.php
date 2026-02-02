<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('updated_by')
                ->nullable()
                ->after('notes')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['league_id', 'matchday_number', 'updated_by'], 'idx_matches_league_day_updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex('idx_matches_league_day_updated_by');
            $table->dropConstrainedForeignId('updated_by');
        });
    }
};
