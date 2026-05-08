<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aligns procedure_setting_steps with the slim API (drops legacy columns, renames flags, adds hours).
 * Idempotent for DBs that already ran an updated 2026_04_29_100013.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            if (Schema::hasColumn('procedure_setting_steps', 'employee_id')) {
                try {
                    Schema::table('procedure_setting_steps', function (Blueprint $table) {
                        $table->dropForeign('ps_steps_employee_fk');
                    });
                } catch (\Throwable) {
                    try {
                        Schema::table('procedure_setting_steps', function (Blueprint $table) {
                            $table->dropForeign(['employee_id']);
                        });
                    } catch (\Throwable) {
                        // No FK (e.g. SQLite) or non-standard name
                    }
                }
                Schema::table('procedure_setting_steps', function (Blueprint $table) {
                    $table->dropColumn('employee_id');
                });
            }

            foreach (['duration', 'approval_form', 'can_approve', 'can_reject'] as $col) {
                if (Schema::hasColumn('procedure_setting_steps', $col)) {
                    Schema::table('procedure_setting_steps', function (Blueprint $table) use ($col) {
                        $table->dropColumn($col);
                    });
                }
            }

            if (Schema::hasColumn('procedure_setting_steps', 'can_view_only')
                && ! Schema::hasColumn('procedure_setting_steps', 'is_view_only')) {
                Schema::table('procedure_setting_steps', function (Blueprint $table) {
                    $table->renameColumn('can_view_only', 'is_view_only');
                });
            }

            if (Schema::hasColumn('procedure_setting_steps', 'can_return_with_notes')
                && ! Schema::hasColumn('procedure_setting_steps', 'is_return_with_notes')) {
                Schema::table('procedure_setting_steps', function (Blueprint $table) {
                    $table->renameColumn('can_return_with_notes', 'is_return_with_notes');
                });
            }

            if (! Schema::hasColumn('procedure_setting_steps', 'approval_within_hours')) {
                Schema::table('procedure_setting_steps', function (Blueprint $table) {
                    $table->unsignedSmallInteger('approval_within_hours')->nullable()->after('approval_within_days');
                });
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        if (Schema::hasColumn('procedure_setting_steps', 'approval_within_hours')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table) {
                $table->dropColumn('approval_within_hours');
            });
        }

        if (Schema::hasColumn('procedure_setting_steps', 'is_return_with_notes')
            && ! Schema::hasColumn('procedure_setting_steps', 'can_return_with_notes')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table) {
                $table->renameColumn('is_return_with_notes', 'can_return_with_notes');
            });
        }

        if (Schema::hasColumn('procedure_setting_steps', 'is_view_only')
            && ! Schema::hasColumn('procedure_setting_steps', 'can_view_only')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table) {
                $table->renameColumn('is_view_only', 'can_view_only');
            });
        }

        Schema::table('procedure_setting_steps', function (Blueprint $table) {
            if (! Schema::hasColumn('procedure_setting_steps', 'duration')) {
                $table->integer('duration')->default(0)->after('is_approve');
            }
            if (! Schema::hasColumn('procedure_setting_steps', 'approval_form')) {
                $table->string('approval_form', 255)->nullable()->after('forms');
            }
            if (! Schema::hasColumn('procedure_setting_steps', 'can_approve')) {
                $table->boolean('can_approve')->default(false)->after('approval_form');
            }
            if (! Schema::hasColumn('procedure_setting_steps', 'can_reject')) {
                $table->boolean('can_reject')->default(false)->after('can_approve');
            }
            if (! Schema::hasColumn('procedure_setting_steps', 'employee_id')) {
                $table->uuid('employee_id')->nullable()->after('name');
            }
        });
    }
};
