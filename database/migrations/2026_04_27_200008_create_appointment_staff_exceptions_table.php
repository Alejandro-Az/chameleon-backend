<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_staff_exceptions', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('appointment_staff_profile_id')
                  ->constrained('appointment_staff_profiles', indexName: 'appt_staff_exc_profile_fk')
                  ->cascadeOnDelete();
            $table->string('name');
            $table->date('exception_date');
            $table->string('type', 32);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['appointment_staff_profile_id', 'exception_date'], 'appointment_staff_exception_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_staff_exceptions');
    }
};
