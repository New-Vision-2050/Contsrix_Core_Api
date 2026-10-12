<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leave_policies', function (Blueprint $table) {
            $table->uuid('leave_type_id')->nullable()->after('company_id');
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');

            // Basic data
            $table->integer('max_balance_days')->nullable()->after('total_days');
            $table->integer('min_notice_days')->nullable()->after('max_balance_days');
            $table->string('day_calculation_type')->nullable()->after('min_notice_days');

            // Entitlement (الاستحقاق)
            $table->string('entitlement_type')->nullable()->after('day_calculation_type');
            $table->string('entitlement_start_from')->nullable()->after('entitlement_type');

            // Promotion (الترقية)
            $table->string('promotion_type')->nullable()->after('entitlement_start_from');
            $table->integer('promotion_duration_months')->nullable()->after('promotion_type');

            // Rollover & options (الترحيل والخيارات)
            $table->integer('rollover_duration_months')->nullable()->after('is_rollover_allowed');
            $table->boolean('allow_exceed_balance')->default(false)->after('rollover_duration_months');

            // Without-balance variant
            $table->integer('max_days')->nullable()->after('allow_exceed_balance');
        });

        Schema::table('leave_policies', function (Blueprint $table) {
            $table->dropColumn(['day_type', 'upgrade_condition', 'max_days_per_request', 'is_allow_half_day']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_policies', function (Blueprint $table) {
            $table->string('day_type')->nullable();
            $table->integer('max_days_per_request')->nullable();
            $table->string('upgrade_condition')->nullable();
            $table->boolean('is_allow_half_day')->default(false);
        });

        Schema::table('leave_policies', function (Blueprint $table) {
            $table->dropForeign(['leave_type_id']);
            $table->dropColumn([
                'leave_type_id',
                'max_balance_days',
                'min_notice_days',
                'day_calculation_type',
                'entitlement_type',
                'entitlement_start_from',
                'promotion_type',
                'promotion_duration_months',
                'rollover_duration_months',
                'allow_exceed_balance',
                'max_days',
            ]);
        });
    }
};
