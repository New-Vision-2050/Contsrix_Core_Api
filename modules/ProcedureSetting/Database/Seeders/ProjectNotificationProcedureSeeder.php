<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Company\CompanyCore\Models\Company;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;

/**
 * Seeds a dedicated parent procedure setting for project-notification tasks
 * (الصيانة والطوارئ) and ensures all internal procedure forms used by the
 * ProjectNotification feature exist under it.
 *
 * Runs per tenant. Safe to re-run (idempotent).
 */
class ProjectNotificationProcedureSeeder extends Seeder
{
    /** Internal forms we want under the project-notification parent. */
    private const FORMS = [
        InternalProcessForm::CreateProjectNotificationTask,
        InternalProcessForm::StartProjectNotificationTask,
        InternalProcessForm::ConfirmProjectNotificationPresence,
        InternalProcessForm::UpdateProjectNotificationTask,
        InternalProcessForm::EndProjectNotificationTask,
    ];

    public function run(): void
    {
        if (! Schema::hasTable('procedure_settings') || ! Schema::hasTable('work_flows')) {
            $this->command?->warn('Procedure settings tables missing. Run migrations first.');

            return;
        }

        $companies = Company::query()->pluck('id');

        if ($companies->isEmpty()) {
            $this->command?->info('No companies found. Nothing to seed.');

            return;
        }

        foreach ($companies as $companyId) {
            $this->seedForCompany((string) $companyId);
        }

        $this->command?->info('ProjectNotificationProcedureSeeder finished.');
    }

    private function seedForCompany(string $companyId): void
    {
        DB::transaction(function () use ($companyId): void {
            $parent = $this->createOrResolveParent($companyId);

            foreach (self::FORMS as $form) {
                $this->ensureInternalProcedure($parent, $form);
            }
        });
    }

    /**
     * Create or resolve the dedicated parent procedure setting for project
     * notification tasks. The parent has its own workflow so it can be
     * configured independently from regular employee tasks.
     */
    private function createOrResolveParent(string $companyId): ProcedureSetting
    {
        $name = 'مهام الصيانة والطوارئ';
        $type = ProcedureSettingType::ProjectNotificationTask->value;

        // Use raw query to bypass tenant global scope.
        $existingId = DB::table('procedure_settings')
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->whereNull('parent_id')
            ->where('name', $name)
            ->value('id');

        if ($existingId !== null) {
            return ProcedureSetting::withoutGlobalScopes()->findOrFail($existingId);
        }

        $workFlow = WorkFlow::query()->create([
            'id'         => (string) Str::uuid(),
            'company_id' => $companyId,
            'name'       => 'default',
            'type'       => $type,
        ]);

        // Mirror branch associations from the company default workflow so the new
        // parent is picked up for the same branches in available-actions.
        // Prefer the project_notification_task default, fall back to employee_task
        // default for backwards compatibility on first migration.
        $defaultWorkFlow = WorkFlow::query()
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->where('name', 'default')
            ->first()
            ?? WorkFlow::query()
                ->where('company_id', $companyId)
                ->where('type', ProcedureSettingType::EmployeeTask->value)
                ->where('name', 'default')
                ->first();

        if ($defaultWorkFlow !== null) {
            $branchIds = $defaultWorkFlow->managementHierarchies()->pluck('management_hierarchies.id')->all();
            $workFlow->managementHierarchies()->syncWithoutDetaching($branchIds);
        }

        $newId = (string) Str::uuid();
        $now   = now();

        DB::table('procedure_settings')->insert([
            'id'           => $newId,
            'company_id'   => $companyId,
            'work_flow_id' => $workFlow->id,
            'name'         => $name,
            'type'         => $type,
            'execute_type' => 'sequence',
            'sort_order'   => 0,
            'percentage'   => 0,
            'is_active'    => true,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        $this->command?->info(sprintf(
            'Created project-notification parent procedure setting for company [%s].',
            $companyId,
        ));

        return ProcedureSetting::withoutGlobalScopes()->findOrFail($newId);
    }

    /**
     * Ensure an internal procedure (child with form set) exists under the given
     * parent. If the child already exists somewhere else (e.g. auto-created by
     * InternalProcedureSettingsSeeder under the default employee_task parent),
     * it is re-parented under the project-notification parent.
     */
    private function ensureInternalProcedure(ProcedureSetting $parent, InternalProcessForm $form): void
    {
        $companyId = $parent->company_id;
        $formValue = $form->value;
        $sortOrder = $form->sortOrder();

        // 1. Already under the project-notification parent?
        $existingId = DB::table('procedure_settings')
            ->where('company_id', $companyId)
            ->where('parent_id', $parent->id)
            ->where('form', $formValue)
            ->value('id');

        if ($existingId !== null) {
            DB::table('procedure_settings')
                ->where('id', $existingId)
                ->update([
                    'sort_order' => $sortOrder,
                    'is_active'  => true,
                    'updated_at' => now(),
                ]);

            $this->command?->info(sprintf(
                'Updated [%s] under project-notification parent.',
                $formValue,
            ));

            return;
        }

        // 2. Exists under another parent? Re-parent it (type may be employee_task
        // from a previous seed or project_notification_task after this change).
        $otherId = DB::table('procedure_settings')
            ->where('company_id', $companyId)
            ->where('form', $formValue)
            ->whereNotNull('parent_id')
            ->where('parent_id', '!=', $parent->id)
            ->value('id');

        if ($otherId !== null) {
            DB::table('procedure_settings')
                ->where('id', $otherId)
                ->update([
                    'parent_id' => $parent->id,
                    'type'      => $parent->type,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);

            $this->command?->info(sprintf(
                'Re-parented [%s] under project-notification parent.',
                $formValue,
            ));

            return;
        }

        // 3. Create a new internal procedure row. Internal procedures don't need
        // their own workflow; they are always fetched by parent_id.
        $now = now();
        DB::table('procedure_settings')->insert([
            'id'           => (string) Str::uuid(),
            'company_id'   => $companyId,
            'parent_id'    => $parent->id,
            'name'         => $form->labelAr(),
            'form'         => $formValue,
            'type'         => $parent->type,
            'execute_type' => 'sequence',
            'sort_order'   => $sortOrder,
            'conditions'   => json_encode(InternalProcessCondition::defaultValuesForForm($form)),
            'percentage'   => 0,
            'is_active'    => true,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        $this->command?->info(sprintf(
            'Created internal procedure [%s] under project-notification parent.',
            $formValue,
        ));
    }
}
