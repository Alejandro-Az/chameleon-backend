<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('public_id', 26)->nullable()->after('id');
        });

        DB::table('roles')->orderBy('id')->chunk(100, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('roles')
                    ->where('id', $row->id)
                    ->update(['public_id' => (string) Str::ulid()]);
            }
        });

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `roles` MODIFY `public_id` VARCHAR(26) NOT NULL');
        } else {
            // SQLite (tests) cannot reliably alter nullability; enforce data integrity.
            $missing = DB::table('roles')->whereNull('public_id')->count();
            if ($missing > 0) {
                throw new RuntimeException('roles.public_id backfill failed');
            }
        }

        Schema::table('roles', function (Blueprint $table): void {
            $table->unique('public_id');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
