<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop break_start_time and break_end_time from the attendances table.
 *
 * These columns were the original single-break storage; they have been
 * superseded by the attendance_breaks table.  AttendanceConstraintService
 * was updated to read from attendance_breaks before this migration runs.
 *
 * Safe to run because:
 *  - Both columns were removed from Attendance::$fillable in an earlier migration.
 *  - AttendanceConstraintService::validateBreakEnd() no longer reads them.
 *  - No other application code references these columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('attendances', 'break_start_time')) {
                $columns[] = 'break_start_time';
            }

            if (Schema::hasColumn('attendances', 'break_end_time')) {
                $columns[] = 'break_end_time';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'break_start_time')) {
                $table->dateTime('break_start_time')->nullable()->after('clock_out_time');
            }

            if (!Schema::hasColumn('attendances', 'break_end_time')) {
                $table->dateTime('break_end_time')->nullable()->after('break_start_time');
            }
        });
    }
};
