<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_module_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('module_key');
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('order');
            $table->timestamps();
            $table->unique(['event_id', 'module_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_module_configs');
    }
};
