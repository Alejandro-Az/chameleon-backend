<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();

            $table->string('name', 200);
            $table->string('email', 200)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('invitation_code', 100);
            $table->unsignedSmallInteger('invited_seats')->default(1);

            $table->enum('rsvp_status', ['pending', 'yes', 'no', 'maybe'])->default('pending');
            $table->unsignedSmallInteger('guests_confirmed')->nullable();
            $table->text('rsvp_message')->nullable();
            $table->boolean('show_in_public_list')->default(false);

            $table->json('dietary_tags')->nullable();
            $table->text('dietary_notes')->nullable();

            $table->string('seat_label', 100)->nullable();
            $table->timestamp('checked_in_at')->nullable();

            $table->unique(['event_id', 'invitation_code']);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
