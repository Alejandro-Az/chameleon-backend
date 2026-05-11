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
        Schema::create('event_dress_codes', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();

            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->text('examples')->nullable();
            $table->text('notes')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_enabled')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_dress_codes');
    }
};
