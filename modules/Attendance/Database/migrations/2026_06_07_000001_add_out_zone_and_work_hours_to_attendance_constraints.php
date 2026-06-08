<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds three new columns to attendance_constraints:
     *   - out_zone_minutes : max duration (in minutes) an employee may stay outside the constraint zone.
     *                        Mirrored in constraint_config.time_rules.out_zone_rules.duration_minutes.
     *   - out_zone_rules   : JSON { requires_approval, approval_threshold_minutes, unit, duration_minutes }
     *                        Full out-of-zone rules object, kept in sync with constraint_config.time_rules.out_zone_rules.
     *   - max_working_hours: Maximum working hours allowed per day across the constraint (default 9).
     *                        Each day's shift periods must not exceed this cap.
     */
    public function up(): void
    {
        Schema::table('attendance_constraints', function (Blueprint $table) {
            $table->unsignedInteger('out_zone_minutes')
                  ->nullable()
                  ->after('max_over_time')
                  ->comment('Max time in minutes an employee may stay outside the constraint zone');

            $table->json('out_zone_rules')
                  ->nullable()
                  ->after('out_zone_minutes')
                  ->comment('Out-of-zone rules: {requires_approval, approval_threshold_minutes, unit, duration_minutes}');

            $table->unsignedTinyInteger('max_working_hours')
                  ->default(9)
                  ->after('out_zone_rules')
                  ->comment('Maximum working hours allowed per day (default 9). Each day\'s shifts must not exceed this.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_constraints', function (Blueprint $table) {
            $table->dropColumn(['out_zone_minutes', 'out_zone_rules', 'max_working_hours']);
        });
    }
};
