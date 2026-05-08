<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('auth_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // jti from JWT or refresh token hash
            $table->string('token_id', 64)->unique();

            $table->string('ip', 45)->nullable(); // IPv4/IPv6
            $table->text('user_agent')->nullable();

            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            // Always set based on JWT TTL
            $table->dateTime('expires_at');

            $table->timestamps();

            $table->index(['user_id', 'revoked_at'], 'idx_user_revoked');
            $table->index(['user_id', 'expires_at'], 'idx_user_expires');

            // Performance: prune commands filter globally by these columns
            $table->index('expires_at', 'idx_auth_sessions_expires_at');
            $table->index('revoked_at', 'idx_auth_sessions_revoked_at');

            // Performance: session listing ordered by last_seen_at per user
            $table->index(['user_id', 'last_seen_at'], 'idx_auth_sessions_user_last_seen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_sessions');
    }
};
