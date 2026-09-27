<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Day-exception (استثناء اليوم) decisions for the rule-based auto clock-out penalty.
 *
 * When a shift is auto-closed with shift_end_method = auto_extension_penalty, the
 * employee may waive the penalty by spending one of their monthly exceptions
 * (attendance.penalty_exceptions_monthly_limit, default 3). One row per penalized
 * attendance — the unique attendance_id makes a second decision impossible.
 *
 * action:
 *  - waived   → the penalty was removed (clock_out_time restored to expected)
 *  - accepted → the employee acknowledged the penalty; it stands
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_penalty_exceptions')) {
            return;
        }

        Schema::create('attendance_penalty_exceptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('user_id')->index();
            $table->uuid('attendance_id')->unique();
            $table->date('business_date')->index();
            $table->string('action', 20);
            // Audit snapshot at decision time (branch-TZ wall-clock strings, like the row).
            $table->unsignedInteger('penalty_minutes')->default(0);
            $table->string('original_clock_out_time', 19)->nullable();
            $table->string('restored_clock_out_time', 19)->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();

            $table->index(['user_id', 'decided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_penalty_exceptions');
    }
};
