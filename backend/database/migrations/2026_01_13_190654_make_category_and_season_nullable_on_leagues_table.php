<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        // 1) Soltar FKs (obligatorio antes de alterar columnas)
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['season_id']);
        });

        // 2) Hacer columnas NULLABLE
        DB::statement('ALTER TABLE leagues MODIFY category_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE leagues MODIFY season_id BIGINT UNSIGNED NULL');

        // 3) Re-crear FKs manteniendo RESTRICT como tenías
        Schema::table('leagues', function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('id')->on('categories')
                ->restrictOnDelete();

            $table->foreign('season_id')
                ->references('id')->on('seasons')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Rollback seguro: si hay NULLs, los rellenamos con el primer id disponible
        $fallbackCategoryId = DB::table('categories')->value('id');
        $fallbackSeasonId   = DB::table('seasons')->value('id');

        if ($fallbackCategoryId) {
            DB::table('leagues')->whereNull('category_id')->update(['category_id' => $fallbackCategoryId]);
        }
        if ($fallbackSeasonId) {
            DB::table('leagues')->whereNull('season_id')->update(['season_id' => $fallbackSeasonId]);
        }

        Schema::table('leagues', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['season_id']);
        });

        DB::statement('ALTER TABLE leagues MODIFY category_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE leagues MODIFY season_id BIGINT UNSIGNED NOT NULL');

        Schema::table('leagues', function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('id')->on('categories')
                ->restrictOnDelete();

            $table->foreign('season_id')
                ->references('id')->on('seasons')
                ->restrictOnDelete();
        });
    }
};
