<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('level', ['pro','semi','amateur']);
            $table->enum('gender', ['mixed','male','female'])->default('mixed');
        });
    }
    public function down(): void {
        Schema::dropIfExists('categories');
    }
};
