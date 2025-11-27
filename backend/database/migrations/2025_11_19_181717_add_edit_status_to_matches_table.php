<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->enum('edit_status', ['open', 'closed', 'verified'])
                ->default('open')
                ->after('status');

            $table->foreignId('verified_by')
                ->nullable()
                ->after('edit_status')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('verified_at')
                ->nullable()
                ->after('verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['edit_status', 'verified_by', 'verified_at']);
        });
    }
};
