<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add procedure_setting_id to processes table.
 *
 * Stores which internal procedure setting (child ProcedureSetting with a form
 * key) this Process represents. When the process reaches Completed status,
 * ProcessWorkflowService fires WorkflowProcedureTaken for this setting so that
 * the available-actions API can correctly unlock downstream procedures.
 *
 * Only populated for child (internal) procedure settings (form != null).
 * Parent-level settings (e.g. ClientRequest top-level) leave this null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processes', function (Blueprint $table): void {
            $table->uuid('procedure_setting_id')
                ->nullable()
                ->after('template_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('processes', function (Blueprint $table): void {
            $table->dropColumn('procedure_setting_id');
        });
    }
};
