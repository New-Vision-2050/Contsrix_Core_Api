<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Calendar day in branch TZ — indexable, eliminates off-by-one bugs at midnight.
            if (!Schema::hasColumn('attendances', 'business_date')) {
                $table->date('business_date')->nullable()->after('timezone');
            }

            // Records how a shift was closed: manual|auto_next_shift|auto_max_ot|auto_radius.
            if (!Schema::hasColumn('attendances', 'shift_end_method')) {
                $table->string('shift_end_method', 32)->nullable()->after('day_status');
            }

            $table->index(['company_id', 'business_date'], 'att_co_bizd_idx');
            $table->index(['user_id', 'business_date'], 'att_user_bizd_idx');
            $table->index(['company_id', 'status', 'start_time'], 'att_co_status_start_idx');
            $table->index(['company_id', 'is_late', 'start_time'], 'att_co_late_start_idx');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            // Document that max_over_time is stored in HOURS (decimal) in this codebase.
            // Phase 2 will normalise to MINUTES once the domain calculator is wired in.
            DB::statement("ALTER TABLE attendances MODIFY max_over_time DECIMAL(8,1) UNSIGNED NULL COMMENT 'Hours (decimal). Snapshot of constraint.max_over_time at clock-in.'");
            DB::statement("ALTER TABLE attendance_constraints MODIFY max_over_time DECIMAL(8,1) UNSIGNED NULL COMMENT 'Hours (decimal). Cap on overtime above scheduled period length.'");

            // Soft guard — MySQL 8.0.16+ only; older versions silently ignore CHECK constraints.
            DB::statement("ALTER TABLE attendances ADD CONSTRAINT chk_att_status CHECK (status IN ('waiting','active','completed','pending_approval','approved','rejected'))");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE attendances DROP CONSTRAINT IF EXISTS chk_att_status');
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('att_co_bizd_idx');
            $table->dropIndex('att_user_bizd_idx');
            $table->dropIndex('att_co_status_start_idx');
            $table->dropIndex('att_co_late_start_idx');

            if (Schema::hasColumn('attendances', 'shift_end_method')) {
                $table->dropColumn('shift_end_method');
            }
            if (Schema::hasColumn('attendances', 'business_date')) {
                $table->dropColumn('business_date');
            }
        });
    }
};
