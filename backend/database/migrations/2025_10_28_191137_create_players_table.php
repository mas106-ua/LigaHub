<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 150);
            $table->date('date_of_birth')->nullable();
            $table->enum('position', ['GK','DF','MF','FW','NA'])->default('NA');
            $table->string('doc_number', 50)->nullable();
        });
    }
    public function down(): void {
        Schema::dropIfExists('players');
    }
};
