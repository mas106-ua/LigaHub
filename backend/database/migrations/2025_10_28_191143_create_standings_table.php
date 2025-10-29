<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('standings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained('leagues')->cascadeOnDelete();
            $table->unsignedSmallInteger('matchday_number');
            $table->longText('table_json');
            $table->timestamp('generated_at')->useCurrent();
            $table->unique(['league_id','matchday_number']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('standings');
    }
};
