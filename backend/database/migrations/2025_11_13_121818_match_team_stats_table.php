<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('match_team_stats', function (Blueprint $t) {
      $t->id();
      $t->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
      $t->foreignId('team_id')->constrained('teams')->restrictOnDelete();
      $t->enum('side', ['home','away'])->index();
      $t->unsignedSmallInteger('possession')->nullable();      // 0..100
      $t->unsignedSmallInteger('shots_total')->nullable();
      $t->unsignedSmallInteger('shots_on_target')->nullable();
      $t->unsignedSmallInteger('corners')->nullable();
      $t->unsignedSmallInteger('fouls')->nullable();
      $t->unsignedSmallInteger('offsides')->nullable();
      $t->unsignedSmallInteger('yellow_cards')->nullable();
      $t->unsignedSmallInteger('red_cards')->nullable();
      $t->timestamps();

      $t->unique(['match_id','side']); // una fila por equipo (home/away)
    });
  }
  public function down(): void { Schema::dropIfExists('match_team_stats'); }
};
