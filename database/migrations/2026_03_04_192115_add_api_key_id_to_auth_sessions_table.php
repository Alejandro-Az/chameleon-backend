<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('auth_sessions', function (Blueprint $table) {
            $table->foreignId('api_key_id')
                ->nullable()
                ->after('user_id')
                ->constrained('api_keys')
                ->nullOnDelete();

            $table->index(['api_key_id', 'revoked_at'], 'idx_auth_sessions_api_key_revoked');
        });
    }

    public function down(): void
    {
        Schema::table('auth_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_auth_sessions_api_key_revoked');
            $table->dropConstrainedForeignId('api_key_id');
        });
    }
};
