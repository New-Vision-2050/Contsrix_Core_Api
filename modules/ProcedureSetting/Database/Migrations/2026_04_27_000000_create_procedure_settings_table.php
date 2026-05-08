<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Base table for procedure settings (was missing; 2026_04_26 only alters it when it already exists).
 */
return new class extends Migration
{
    private const PS_COMPANY_FK = 'ps_settings_company_fk';

    private const PS_ESCAL_USER_FK = 'ps_settings_esc_user_fk';

    private const PS_WORK_FLOW_FK = 'ps_settings_work_flow_fk';

    public function up(): void
    {
        if (Schema::hasTable('procedure_settings')) {
            return;
        }

        Schema::create('procedure_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type');
            $table->string('execute_type');
            $table->string('icon')->nullable();
            $table->decimal('percentage', 5, 2)->default(0);
            $table->unsignedInteger('deadline_days')->nullable();
            $table->unsignedInteger('deadline_hours')->nullable();
            $table->uuid('escalation_user_id')->nullable();
            $table->uuid('company_id')->nullable()->index();
            $table->uuid('work_flow_id')->nullable()->index();
            $table->timestamps();
        });

        if (Schema::hasTable('companies')) {
            Schema::table('procedure_settings', function (Blueprint $table) {
                $table->foreign('company_id', self::PS_COMPANY_FK)
                    ->references('id')
                    ->on('companies')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('procedure_settings', function (Blueprint $table) {
                $table->foreign('escalation_user_id', self::PS_ESCAL_USER_FK)
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('work_flows')) {
            Schema::table('procedure_settings', function (Blueprint $table) {
                $table->foreign('work_flow_id', self::PS_WORK_FLOW_FK)
                    ->references('id')
                    ->on('work_flows')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_settings');
    }
};
