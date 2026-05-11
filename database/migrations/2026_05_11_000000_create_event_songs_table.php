<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_songs', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique(); // ULID público para API
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('suggested_by_guest_id')->nullable()->constrained('guests')->nullOnDelete();

            $table->string('title', 150);
            $table->string('artist', 150)->nullable();
            $table->string('url', 255)->nullable();
            $table->string('message_for_couple', 500)->nullable();
            $table->boolean('show_author')->default(true);
            $table->string('status', 20)->default('approved'); // pending | approved | rejected
            $table->unsignedInteger('votes_count')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_songs');
    }
};
