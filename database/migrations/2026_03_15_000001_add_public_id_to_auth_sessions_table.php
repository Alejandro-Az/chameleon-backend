<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auth_sessions', function (Blueprint $table) {
            $table->string('public_id', 26)->nullable()->after('id')->unique();
        });

        // Backfill existing rows in chunks to avoid memory/time issues
        DB::table('auth_sessions')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                DB::table('auth_sessions')
                    ->where('id', $row->id)
                    ->update(['public_id' => (string) Str::ulid()]);
            }
        });

        // Make column not nullable once backfill completed
        try {
            Schema::table('auth_sessions', function (Blueprint $table) {
                $table->string('public_id', 26)->unique()->nullable(false)->change();
            });
        } catch (\Exception $e) {
            // Some environments may not have doctrine/dbal for change(); leave nullable if change fails.
        }
    }

    public function down(): void
    {
        Schema::table('auth_sessions', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
