<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('match_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->string('file_path');
            $table->timestamp('generated_at')->useCurrent();
            $table->string('checksum', 64)->nullable();
        });
    }
    public function down(): void {
        Schema::dropIfExists('match_reports');
    }
};
