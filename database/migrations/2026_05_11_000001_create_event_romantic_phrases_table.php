<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_romantic_phrases', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique(); // ULID público para API
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();

            $table->string('phrase', 500);          // Texto de la frase romántica
            $table->string('author', 150)->nullable(); // Autor de la frase (opcional)

            $table->integer('display_order')->default(0); // Orden de presentación
            $table->boolean('is_enabled')->default(true); // Visible/oculto

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_romantic_phrases');
    }
};
