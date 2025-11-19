<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ⚠️ Esto borra la tabla antigua. Si por lo que sea tuvieses datos reales,
        // habría que hacer un backup antes.
        Schema::dropIfExists('match_events');

        Schema::create('match_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('match_id')
                ->constrained('matches')
                ->cascadeOnDelete();

            // Lado del evento: local/visitante
            $table->enum('side', ['home', 'away']);

            // Minuto base + añadido (p.ej. 45+2)
            $table->unsignedSmallInteger('minute');
            $table->unsignedTinyInteger('extra_minute')->default(0);

            // Tipo de evento (texto para no estar peleándonos con enums)
            $table->string('type', 32);

            // Jugador principal
            $table->foreignId('player_id')
                ->nullable()
                ->constrained('players')
                ->nullOnDelete();

            // Jugador relacionado (asistente, el que entra/sale, etc.)
            $table->foreignId('related_player_id')
                ->nullable()
                ->constrained('players')
                ->nullOnDelete();

            // Notas / VAR / texto libre
            $table->text('description')->nullable();

            // Auditoría mínima
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['match_id', 'minute', 'extra_minute', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_events');
    }
};
