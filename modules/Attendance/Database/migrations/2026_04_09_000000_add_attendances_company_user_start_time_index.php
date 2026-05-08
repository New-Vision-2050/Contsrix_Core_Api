<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supports team/user attendance export queries:
     * WHERE company_id = ? AND user_id = ? AND start_time < ? ORDER BY start_time ASC
     * Avoids large filesorts / sort-buffer exhaustion on big tables.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(
                ['company_id', 'user_id', 'start_time'],
                'attendances_company_user_start_time_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_company_user_start_time_index');
        });
    }
};
