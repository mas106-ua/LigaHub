<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('league_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained('leagues')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role_in_league', ['owner','admin','member','viewer'])->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->unique(['league_id','user_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('league_memberships');
    }
};
