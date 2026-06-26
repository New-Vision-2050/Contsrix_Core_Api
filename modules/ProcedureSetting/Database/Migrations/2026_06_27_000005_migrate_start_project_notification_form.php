<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The StartProjectNotificationTask form was removed from the
     * InternalProcessForm enum. This migration deletes any leftover
     * procedure_settings rows that still use that form, along with
     * their associated workflow records.
     *
     * Related tables (procedure_setting_steps, internal_procedure_takens)
     * have cascadeOnDelete on procedure_setting_id, so they are cleaned up
     * automatically.
     */
    public function up(): void
    {
        if (! Schema::hasTable('procedure_settings')) {
            return;
        }

        $form = 'startProjectNotificationTask';

        // 1. Collect work_flow_ids used by the procedure settings being deleted
        //    so we can clean up orphaned workflows afterwards.
        $workFlowIds = DB::table('procedure_settings')
            ->where('form', $form)
            ->whereNotNull('work_flow_id')
            ->pluck('work_flow_id')
            ->unique()
            ->all();

        // 2. Delete the procedure_settings rows. Steps and takens cascade-delete.
        DB::table('procedure_settings')
            ->where('form', $form)
            ->delete();

        // 3. Delete orphaned work_flows that are no longer referenced by any
        //    procedure_setting. Only touch the ones we collected above.
        foreach ($workFlowIds as $workFlowId) {
            $stillUsed = DB::table('procedure_settings')
                ->where('work_flow_id', $workFlowId)
                ->exists();

            if (! $stillUsed) {
                // Clean up branch associations first.
                if (Schema::hasTable('management_hierarchy_work_flow')) {
                    DB::table('management_hierarchy_work_flow')
                        ->where('work_flow_id', $workFlowId)
                        ->delete();
                }

                DB::table('work_flows')
                    ->where('id', $workFlowId)
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        // Cannot restore deleted procedure settings.
    }
};
