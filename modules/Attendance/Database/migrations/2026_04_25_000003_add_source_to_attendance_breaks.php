<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_breaks', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_breaks', 'source')) {
                // 'auto_gap' = computed from clock-out/clock-in gap (current behaviour).
                // 'manual' reserved for a future explicit break API.
                $table->string('source', 16)->default('auto_gap')->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_breaks', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_breaks', 'source')) {
                $table->dropColumn('source');
            }
        });
    }
};
