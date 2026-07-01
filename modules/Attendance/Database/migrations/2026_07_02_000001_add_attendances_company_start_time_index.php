<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supports team attendance queries that filter by company_id and start_time
     * range without a user_id filter, and ORDER BY start_time.
     * Avoids filesort / sort-buffer exhaustion on large tables.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(
                ['company_id', 'start_time'],
                'attendances_company_start_time_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_company_start_time_index');
        });
    }
};
