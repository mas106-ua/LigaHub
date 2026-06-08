<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->string('schedule_status')->default('draft')->after('type');
            $table->string('schedule_type')->nullable()->after('schedule_status'); // single | double
            $table->timestamp('schedule_published_at')->nullable()->after('schedule_type');
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn([
                'schedule_status',
                'schedule_type',
                'schedule_published_at',
            ]);
        });
    }
};
