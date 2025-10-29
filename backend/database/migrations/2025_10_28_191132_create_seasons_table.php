<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // '2024/25'
            $table->date('start_date');
            $table->date('end_date');
        });
    }
    public function down(): void {
        Schema::dropIfExists('seasons');
    }
};
