<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->ulid('public_id')->unique();

            $table->string('name');
            $table->string('email')->unique();

            // Optional username for login; MySQL UNIQUE allows multiple NULLs
            $table->string('username', 50)->nullable()->unique();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // active|suspended|pending
            $table->string('status', 20)->default('pending');
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('last_login_at')->nullable();

            $table->string('timezone', 50)->nullable();
            $table->string('locale', 10)->default('es');

            $table->json('meta')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'idx_status');
            $table->index('last_login_at', 'idx_last_login');
            $table->index('deleted_at', 'idx_deleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
