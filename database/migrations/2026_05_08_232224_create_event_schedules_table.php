<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_schedules', function (Blueprint $table) {
            $table->id(); // BigInt auto-increment (PKI interno)
            $table->string('public_id', 26)->unique(); // ULID público para API
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();

            // Contenido
            $table->string('title', 150); // Ej: "Ceremonia", "Recepción"
            $table->longText('description')->nullable(); // Descripción detallada
            $table->dateTime('starts_at'); // Hora inicio del evento
            $table->dateTime('ends_at')->nullable(); // Hora fin (opcional)

            // Ubicación contextual
            $table->string('location_label', 150)->nullable(); // Ej: "Salón principal", "Jardín trasero"
            $table->string('location_type', 50)->nullable(); // Ej: "ceremony", "reception", "dinner", "cocktail"

            // Ordenamiento y visibilidad
            $table->integer('display_order')->default(0); // Orden en itinerario
            $table->boolean('is_enabled')->default(true); // Visible/oculto

            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_schedules');
    }
};
