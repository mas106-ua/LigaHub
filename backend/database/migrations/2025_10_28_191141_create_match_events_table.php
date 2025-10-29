<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('match_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->unsignedTinyInteger('minute')->nullable();
            $table->enum('type', ['goal','own_goal','yellow','red','sub_in','sub_out']);
            $table->foreignId('player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('detail', 255)->nullable();

            $table->index('match_id');
        });
    }
    public function down(): void {
        Schema::dropIfExists('match_events');
    }
};
