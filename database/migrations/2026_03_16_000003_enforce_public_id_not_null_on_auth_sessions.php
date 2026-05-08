<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Defensive backfill in case older environments still have NULL values.
        DB::table('auth_sessions')
            ->whereNull('public_id')
            ->orderBy('id')
            ->chunk(100, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('auth_sessions')
                        ->where('id', $row->id)
                        ->update(['public_id' => (string) Str::ulid()]);
                }
            });

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `auth_sessions` MODIFY `public_id` VARCHAR(26) NOT NULL');
        } else {
            $missing = DB::table('auth_sessions')->whereNull('public_id')->count();
            if ($missing > 0) {
                throw new RuntimeException('auth_sessions.public_id contains NULL values');
            }
        }
    }

    public function down(): void
    {
        // Intentionally left empty:
        // this migration enforces integrity for existing environments.
    }
};
