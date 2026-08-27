<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        DB::table('public_holiday_days')->truncate();
        DB::table('public_holidays')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function down(): void
    {
        // No rollback action — data was seeded and is intentionally removed
    }
};
