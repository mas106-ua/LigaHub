<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('match_lineups', function (Blueprint $t) {
      $t->id();
      $t->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
      $t->foreignId('team_id')->constrained('teams')->restrictOnDelete();
      $t->enum('side', ['home','away'])->index();
      $t->string('formation', 10)->nullable();    // p.ej. 4-3-3
      $t->string('coach_name', 120)->nullable();
      $t->json('starters')->nullable(); // [{player_id, shirt, pos}]
      $t->json('bench')->nullable();    // idem
      $t->timestamps();

      $t->unique(['match_id','side']);
    });
  }
  public function down(): void { Schema::dropIfExists('match_lineups'); }
};
