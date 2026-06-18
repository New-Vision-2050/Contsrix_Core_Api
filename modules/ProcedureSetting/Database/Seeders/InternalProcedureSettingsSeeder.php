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
 * Seeds default InternalProcedureSettings (child procedure_settings with form set)
 * for every ProcedureSettingType, per company.
 *
 * Finds or creates a parent ProcedureSetting for each type, then seeds the children.
 * Safe to re-run (skips existing form entries per parent).
 */
class InternalProcedureSettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasColumn('procedure_settings', 'parent_id')) {
            $this->command?->warn('parent_id column missing. Run migrations first.');

            return;
        }

        $companies = Company::query()->pluck('id');

        if ($companies->isEmpty()) {
            $this->command?->info('No companies found. Nothing to seed.');

            return;
        }

        foreach ($companies as $companyId) {
            foreach (ProcedureSettingType::cases() as $type) {
                $parent = $this->resolveParent((string) $companyId, $type);
                $this->seedForParent($parent);
            }
        }

        $this->command?->info('InternalProcedureSettingsSeeder finished.');
    }

    private function resolveParent(string $companyId, ProcedureSettingType $type): ProcedureSetting
    {
        $workFlow = WorkFlow::defaultForCompany($companyId, $type->value);

        // Use raw query to bypass tenant global scope completely.
        $existingId = DB::table('procedure_settings')
            ->where('company_id', $companyId)
            ->where('type', $type->value)
            ->whereNull('parent_id')
            ->whereNull('form')
            ->value('id');

        if ($existingId !== null) {
            return ProcedureSetting::withoutGlobalScopes()->findOrFail($existingId);
        }

        // Use DB::table() to bypass BelongsToTenant which overrides company_id on create.
        $newId = (string) Str::uuid();
        $now   = now();

        DB::table('procedure_settings')->insert([
            'id'           => $newId,
            'company_id'   => $companyId,
            'work_flow_id' => $workFlow->id,
            'name'         => $type->labelAr(),
            'type'         => $type->value,
            'execute_type' => 'sequence',
            'sort_order'   => 0,
            'percentage'   => 0,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        return ProcedureSetting::withoutGlobalScopes()->findOrFail($newId);
    }

    private function seedForParent(ProcedureSetting $parent): void
    {
        $type = $parent->type;

        // All forms for this type, sorted by sortOrder() (create* → middle → end*).
        $allForms = InternalProcessForm::forType($type);

        // Ensure every existing child's sort_order reflects the canonical weight,
        // even if it was seeded by an older version of this seeder.
        foreach ($allForms as $form) {
            DB::table('procedure_settings')
                ->where('parent_id', $parent->id)
                ->where('form', $form->value)
                ->update(['sort_order' => $form->sortOrder(), 'updated_at' => now()]);
        }

        // Only auto-create children for create*/end* forms.
        $forms = array_values(
            array_filter(
                $allForms,
                static fn (InternalProcessForm $form): bool =>
                    str_starts_with($form->value, 'create') ||
                    str_starts_with($form->value, 'end'),
            )
        );

        if ($forms === []) {
            return;
        }

        foreach ($forms as $form) {
            $sortOrder = $form->sortOrder();

            // Use raw query to bypass tenant global scope completely.
            $existingId = DB::table('procedure_settings')
                ->where('parent_id', $parent->id)
                ->where('form', $form->value)
                ->value('id');

            if ($existingId !== null) {
                // Fix sort_order on existing records so ordering is always correct.
                DB::table('procedure_settings')
                    ->where('id', $existingId)
                    ->update(['sort_order' => $sortOrder, 'updated_at' => now()]);

                $this->command?->info(sprintf(
                    'Updated sort_order for [%s] under [%s] → %d.',
                    $form->value,
                    $parent->name,
                    $sortOrder,
                ));
                continue;
            }

            $workFlow = $this->createWorkFlowForInternal($parent, $form->value, $type);

            // Use DB::table() to bypass BelongsToTenant which overrides company_id on create.
            $now = now();
            DB::table('procedure_settings')->insert([
                'id'           => (string) Str::uuid(),
                'company_id'   => $parent->company_id,
                'work_flow_id' => $workFlow->id,
                'parent_id'    => $parent->id,
                'name'         => $form->labelAr(),
                'form'         => $form->value,
                'type'         => $type,
                'execute_type' => 'sequence',
                'sort_order'   => $sortOrder,
                'conditions'   => json_encode(InternalProcessCondition::defaultValuesForForm($form)),
                'percentage'   => 0,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            $this->command?->info(sprintf(
                'Created InternalProcedure [%s] under [%s].',
                $form->value,
                $parent->name,
            ));
        }
    }

    private function createWorkFlowForInternal(ProcedureSetting $parent, string $formValue, string $type): WorkFlow
    {
        $workFlow = WorkFlow::query()->create([
            'id'         => (string) Str::uuid(),
            'company_id' => $parent->company_id,
            'name'       => $formValue,
            'type'       => $type,
        ]);

        // Copy branch associations from parent workflow
        $parentWorkFlow = WorkFlow::query()->find($parent->work_flow_id);
        if ($parentWorkFlow !== null) {
            $branchIds = $parentWorkFlow->managementHierarchies()->pluck('management_hierarchies.id')->all();
            $workFlow->managementHierarchies()->syncWithoutDetaching($branchIds);
        }

        return $workFlow;
    }
}
