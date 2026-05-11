<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gifts', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();

            $table->string('name', 150);
            $table->string('description', 500)->nullable();
            $table->string('store_label', 100)->nullable();
            $table->string('url', 500)->nullable();

            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedSmallInteger('quantity_reserved')->default(0);

            $table->string('status', 20)->default('pending'); // pending | reserved | purchased

            $table->integer('display_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gifts');
    }
};
