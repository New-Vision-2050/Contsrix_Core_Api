<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;

return new class extends Migration
{
    /**
     * Migrate the dedicated "مهام الصيانة والطوارئ" parent and its children from
     * employee_task to the new project_notification_task type. This keeps the
     * existing UI row while separating it from regular employee tasks.
     */
    public function up(): void
    {
        if (! Schema::hasTable('procedure_settings') || ! Schema::hasTable('work_flows')) {
            return;
        }

        $oldType = ProcedureSettingType::EmployeeTask->value;
        $newType = ProcedureSettingType::ProjectNotificationTask->value;
        $parentName = 'مهام الصيانة والطوارئ';

        // 1. Move any project-notification-specific internal procedures that were
        // auto-created under a generic employee_task parent to the dedicated parent.
        $parents = DB::table('procedure_settings')
            ->where('type', $oldType)
            ->whereNull('parent_id')
            ->where('name', $parentName)
            ->get();

        foreach ($parents as $parent) {
            // Ensure a default workflow exists for the new type.
            $workFlow = DB::table('work_flows')
                ->where('company_id', $parent->company_id)
                ->where('type', $newType)
                ->where('name', 'default')
                ->first();

            if ($workFlow === null) {
                $workFlowId = (string) Str::uuid();
                DB::table('work_flows')->insert([
                    'id'         => $workFlowId,
                    'company_id' => $parent->company_id,
                    'name'       => 'default',
                    'type'       => $newType,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Mirror branches from the employee_task default workflow so the new
                // parent is picked up for the same branches.
                $defaultWorkFlow = DB::table('work_flows')
                    ->where('company_id', $parent->company_id)
                    ->where('type', $oldType)
                    ->where('name', 'default')
                    ->first();

                if ($defaultWorkFlow !== null) {
                    $branchIds = DB::table('management_hierarchy_work_flow')
                        ->where('work_flow_id', $defaultWorkFlow->id)
                        ->pluck('management_hierarchy_id')
                        ->all();

                    $now = now();
                    foreach ($branchIds as $branchId) {
                        DB::table('management_hierarchy_work_flow')->insertOrIgnore([
                            'work_flow_id' => $workFlowId,
                            'management_hierarchy_id' => $branchId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            } else {
                $workFlowId = $workFlow->id;
            }

            // 2. Update the parent itself to the new type and default workflow.
            DB::table('procedure_settings')
                ->where('id', $parent->id)
                ->update([
                    'type'         => $newType,
                    'work_flow_id' => $workFlowId,
                    'updated_at'   => now(),
                ]);

            // 3. Update all children under this parent to the new type.
            DB::table('procedure_settings')
                ->where('parent_id', $parent->id)
                ->update([
                    'type'       => $newType,
                    'updated_at' => now(),
                ]);
        }

        // 4. Update any project-notification forms that may still live under another
        // employee_task parent so they belong to a project_notification_task parent.
        $projectNotificationForms = [
            'createProjectNotificationTask',
            'startProjectNotificationTask',
            'confirmProjectNotificationPresence',
            'updateProjectNotificationTask',
            'endProjectNotificationTask',
        ];

        foreach ($projectNotificationForms as $form) {
            $children = DB::table('procedure_settings')
                ->where('type', $oldType)
                ->where('form', $form)
                ->whereNotNull('parent_id')
                ->get();

            foreach ($children as $child) {
                $parent = DB::table('procedure_settings')
                    ->where('id', $child->parent_id)
                    ->where('type', $oldType)
                    ->first();

                if ($parent === null) {
                    continue;
                }

                // Find or create a project_notification_task parent for this company.
                $newParent = DB::table('procedure_settings')
                    ->where('company_id', $parent->company_id)
                    ->where('type', $newType)
                    ->whereNull('parent_id')
                    ->where('name', $parentName)
                    ->first();

                if ($newParent === null) {
                    $newWorkFlowId = DB::table('work_flows')
                        ->where('company_id', $parent->company_id)
                        ->where('type', $newType)
                        ->where('name', 'default')
                        ->value('id');

                    $newParentId = (string) Str::uuid();
                    DB::table('procedure_settings')->insert([
                        'id'           => $newParentId,
                        'company_id'   => $parent->company_id,
                        'work_flow_id' => $newWorkFlowId,
                        'name'         => $parentName,
                        'type'         => $newType,
                        'execute_type' => 'sequence',
                        'sort_order'   => 0,
                        'percentage'   => 0,
                        'is_active'    => true,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                } else {
                    $newParentId = $newParent->id;
                }

                DB::table('procedure_settings')
                    ->where('id', $child->id)
                    ->update([
                        'parent_id'  => $newParentId,
                        'type'       => $newType,
                        'updated_at' => now(),
                    ]);
            }
        }

        // 5. Migrate any existing project-notification processes/taken records so
        // in-flight approvals continue to resolve after the type change.
        if (Schema::hasTable('processes')) {
            DB::table('processes')
                ->where('processable_type', $oldType)
                ->whereIn('processable_id', function ($query) {
                    $query->select('id')
                        ->from('employee_task_requests')
                        ->where('is_project_notification', true);
                })
                ->update([
                    'processable_type' => $newType,
                    'updated_at'       => now(),
                ]);
        }

        if (Schema::hasTable('internal_procedure_takens')) {
            DB::table('internal_procedure_takens')
                ->where('processable_type', $oldType)
                ->whereIn('processable_id', function ($query) {
                    $query->select('id')
                        ->from('employee_task_requests')
                        ->where('is_project_notification', true);
                })
                ->update([
                    'processable_type' => $newType,
                    'updated_at'       => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Reverting the type change is not safe because new project-notification
        // processes may have already been created under project_notification_task.
    }
};
