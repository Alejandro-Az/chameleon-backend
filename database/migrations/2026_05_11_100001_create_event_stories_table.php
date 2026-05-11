<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_stories', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('title', 150)->nullable();
            $table->string('subtitle', 255)->nullable();
            $table->text('body');
            $table->integer('display_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_stories');
    }
};
