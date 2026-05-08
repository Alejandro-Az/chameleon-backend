<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();

            $table->ulid('public_id')->unique();

            // Owner (service account user)
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Admin creator (human). If deleted, keep key but null creator.
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('name');
            $table->string('prefix', 64)->index('idx_api_keys_prefix');

            // SHA-256 hex (64 chars) of plaintext key
            $table->char('key_hash', 64)->unique();

            $table->json('scopes')->nullable();

            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            // Performance for admin listing + prune
            $table->index(['user_id', 'revoked_at'], 'idx_api_keys_user_revoked');
            $table->index(['user_id', 'expires_at'], 'idx_api_keys_user_expires');
            $table->index('expires_at', 'idx_api_keys_expires_at');
            $table->index('revoked_at', 'idx_api_keys_revoked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
