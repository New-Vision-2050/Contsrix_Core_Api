<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * جدول خطوات الإعداد + الجداول الفرعية (متخذو الإجراء، المعنيون بالإجراء).
 * يعتمد على procedure_settings و users و companies و management_hierarchies.
 */
return new class extends Migration
{
    private const PS_STEPS_BRANCH_FK = 'ps_steps_branch_mh_fk';

    private const PS_STEPS_MANAGEMENT_FK = 'ps_steps_management_mh_fk';

    private const PS_STEPS_ESCAL_USER_FK = 'ps_steps_esc_user_fk';

    private const PS_STEP_AT_STEP_FK = 'ps_step_at_step_fk';

    private const PS_STEP_AT_USER_FK = 'ps_step_at_user_fk';

    private const PS_STEP_AT_COMPANY_FK = 'ps_step_at_company_fk';

    private const PS_STEP_CU_STEP_FK = 'ps_step_cu_step_fk';

    private const PS_STEP_CU_USER_FK = 'ps_step_cu_user_fk';

    private const PS_STEP_CU_COMPANY_FK = 'ps_step_cu_company_fk';

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            Schema::dropIfExists('procedure_setting_step_concerned_users');
            Schema::dropIfExists('procedure_setting_step_action_takers');
            Schema::dropIfExists('procedure_setting_steps');

            Schema::create('procedure_setting_steps', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('management_id')->nullable();
                $table->boolean('is_accept')->default(false);
                $table->boolean('is_approve')->default(false);
                $table->enum('forms', ['approve', 'accept', 'financial'])->nullable();
                $table->boolean('is_view_only')->default(false);
                $table->boolean('is_return_with_notes')->default(false);
                $table->boolean('requires_approval_within_period')->default(false);
                $table->unsignedSmallInteger('approval_within_days')->nullable();
                $table->unsignedSmallInteger('approval_within_hours')->nullable();
                $table->boolean('notify_by_email')->default(false);
                $table->boolean('notify_by_whatsapp')->default(false);
                $table->uuid('procedure_setting_id');
                $table->uuid('company_id')->nullable();
                $table->uuid('escalation_user_id')->nullable();
                $table->timestamps();

                $table->index('procedure_setting_id', 'ps_steps_proc_set_idx');

                $table->foreign('procedure_setting_id', 'ps_steps_psetting_fk')
                    ->references('id')
                    ->on('procedure_settings')
                    ->cascadeOnDelete();

                $table->foreign('company_id', 'ps_steps_company_fk')
                    ->references('id')
                    ->on('companies')
                    ->cascadeOnDelete();
            });

            if (Schema::hasTable('management_hierarchies')) {
                Schema::table('procedure_setting_steps', function (Blueprint $table) {
                    if (! $this->foreignKeyExists('procedure_setting_steps', self::PS_STEPS_BRANCH_FK)) {
                        $table->foreign('branch_id', self::PS_STEPS_BRANCH_FK)
                            ->references('id')
                            ->on('management_hierarchies')
                            ->nullOnDelete();
                    }
                    if (! $this->foreignKeyExists('procedure_setting_steps', self::PS_STEPS_MANAGEMENT_FK)) {
                        $table->foreign('management_id', self::PS_STEPS_MANAGEMENT_FK)
                            ->references('id')
                            ->on('management_hierarchies')
                            ->nullOnDelete();
                    }
                });
            }

            if (Schema::hasTable('users')) {
                Schema::table('procedure_setting_steps', function (Blueprint $table) {
                    if (! $this->foreignKeyExists('procedure_setting_steps', self::PS_STEPS_ESCAL_USER_FK)) {
                        $table->foreign('escalation_user_id', self::PS_STEPS_ESCAL_USER_FK)
                            ->references('id')
                            ->on('users')
                            ->nullOnDelete();
                    }
                });
            }

            Schema::create('procedure_setting_step_action_takers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('procedure_setting_step_id');
                $table->uuid('user_id');
                $table->uuid('company_id')->nullable();
                $table->timestamps();

                $table->foreign('procedure_setting_step_id', self::PS_STEP_AT_STEP_FK)
                    ->references('id')
                    ->on('procedure_setting_steps')
                    ->cascadeOnDelete();

                if (Schema::hasTable('users')) {
                    $table->foreign('user_id', self::PS_STEP_AT_USER_FK)
                        ->references('id')
                        ->on('users')
                        ->cascadeOnDelete();
                }

                if (Schema::hasTable('companies')) {
                    $table->foreign('company_id', self::PS_STEP_AT_COMPANY_FK)
                        ->references('id')
                        ->on('companies')
                        ->cascadeOnDelete();
                }

                $table->unique(['procedure_setting_step_id', 'user_id'], 'ps_step_action_takers_step_user_unique');
            });

            Schema::create('procedure_setting_step_concerned_users', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('procedure_setting_step_id');
                $table->uuid('user_id');
                $table->uuid('company_id')->nullable();
                $table->timestamps();

                $table->foreign('procedure_setting_step_id', self::PS_STEP_CU_STEP_FK)
                    ->references('id')
                    ->on('procedure_setting_steps')
                    ->cascadeOnDelete();

                if (Schema::hasTable('users')) {
                    $table->foreign('user_id', self::PS_STEP_CU_USER_FK)
                        ->references('id')
                        ->on('users')
                        ->cascadeOnDelete();
                }

                if (Schema::hasTable('companies')) {
                    $table->foreign('company_id', self::PS_STEP_CU_COMPANY_FK)
                        ->references('id')
                        ->on('companies')
                        ->cascadeOnDelete();
                }

                $table->unique(['procedure_setting_step_id', 'user_id'], 'ps_step_concerned_users_step_user_unique');
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            Schema::dropIfExists('procedure_setting_step_concerned_users');
            Schema::dropIfExists('procedure_setting_step_action_takers');
            Schema::dropIfExists('procedure_setting_steps');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $result = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND CONSTRAINT_NAME = ?
             AND CONSTRAINT_TYPE = ?',
            [$table, $constraintName, 'FOREIGN KEY']
        );

        return count($result) > 0;
    }
};
