<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_staff_weekday_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_staff_profile_id')
                  ->constrained('appointment_staff_profiles', indexName: 'appt_staff_wkday_profile_fk')
                  ->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->boolean('is_active')->default(true);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
            $table->unique(['appointment_staff_profile_id', 'weekday'], 'appointment_staff_weekday_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_staff_weekday_rules');
    }
};
