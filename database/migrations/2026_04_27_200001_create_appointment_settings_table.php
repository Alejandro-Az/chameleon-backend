<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_timezone', 64)->default('America/Mexico_City');
            $table->unsignedSmallInteger('slot_resolution_minutes')->default(30);
            $table->boolean('holidays_auto_block')->default(true);
            $table->unsignedSmallInteger('self_service_cancel_hours')->default(24);
            $table->unsignedSmallInteger('self_service_reschedule_hours')->default(24);
            $table->boolean('auto_confirm_new_appointments')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_settings');
    }
};
