<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->enum('type', ['official','private']);
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('season_id')->constrained()->restrictOnDelete();
            $table->enum('visibility', ['private','by_link','public'])->default('private');
            $table->uuid('access_uuid')->nullable()->unique();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('region_id');
            $table->index('season_id');
        });
    }
    public function down(): void {
        Schema::dropIfExists('leagues');
    }
};
