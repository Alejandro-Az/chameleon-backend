<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('event_module_configs', function (Blueprint $table) {
            $table->boolean('auto_approve')->default(true)->after('enabled');
        });
    }

    public function down(): void
    {
        Schema::table('event_module_configs', function (Blueprint $table) {
            $table->dropColumn('auto_approve');
        });
    }
};
