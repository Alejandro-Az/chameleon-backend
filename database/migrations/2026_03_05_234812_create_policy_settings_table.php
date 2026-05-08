<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('policy_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value_json')->nullable();          // non-sensitive
            $table->longText('value_encrypted')->nullable(); // sensitive
            $table->foreignId('updated_by_user_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('updated_by_user_id', 'idx_policy_settings_updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_settings');
    }
};
