<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('public_holiday_days')->truncate();
        DB::table('public_holidays')->truncate();
    }

    public function down(): void
    {
        // No rollback action — data was seeded and is intentionally removed
    }
};
