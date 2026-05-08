<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('status', 20)->default('failed'); // success|failed|blocked
            $table->dateTime('blocked_until')->nullable()->index();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['email', 'created_at']);

            // Performance: admin filters by IP and status
            $table->index('ip_address', 'idx_login_attempts_ip');
            $table->index('status', 'idx_login_attempts_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
