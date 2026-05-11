<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_photos', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique(); // ULID público para API
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained('guests')->nullOnDelete();

            $table->string('type', 50)->default('gallery'); // gallery, hero, guest_upload, dress_code, story
            $table->string('file_path', 500);
            $table->string('thumbnail_path', 500)->nullable();
            $table->string('caption', 255)->nullable();
            $table->string('status', 20)->default('approved'); // approved, pending, rejected
            $table->integer('display_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_photos');
    }
};
