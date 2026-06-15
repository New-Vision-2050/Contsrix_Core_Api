<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Database\Seeders;

use Illuminate\Database\Seeder;
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

        // Bypass tenant scope so we can manage records for *any* company during seeding.
        return ProcedureSetting::withoutGlobalScopes()
            ->firstOrCreate(
                [
                    'company_id' => $companyId,
                    'type'       => $type->value,
                    'parent_id'  => null,
                    'form'       => null,
                ],
                [
                    'id'           => (string) Str::uuid(),
                    'work_flow_id' => $workFlow->id,
                    'name'         => $type->labelAr(),
                    'execute_type' => 'sequence',
                    'sort_order'   => 0,
                    'percentage'   => 0,
                ]
            );
    }

    private function seedForParent(ProcedureSetting $parent): void
    {
        $type = $parent->type;

        $forms = InternalProcessForm::forType($type);

        if ($forms === []) {
            return;
        }

        foreach ($forms as $order => $form) {
            // Bypass tenant scope so the exists check works for any company.
            $child = ProcedureSetting::withoutGlobalScopes()
                ->firstOrCreate(
                    [
                        'parent_id' => $parent->id,
                        'form'      => $form->value,
                    ],
                    [
                        'id'           => (string) Str::uuid(),
                        'company_id'   => $parent->company_id,
                        'name'         => $form->labelAr(),
                        'type'         => $type,
                        'execute_type' => 'sequence',
                        'sort_order'   => $order,
                        'conditions'   => InternalProcessCondition::defaultValuesForForm($form),
                        'percentage'   => 0,
                    ]
                );

            if (! $child->wasRecentlyCreated) {
                $this->command?->info(sprintf(
                    'InternalProcedure [%s] already exists under [%s]. Skipping.',
                    $form->value,
                    $parent->name,
                ));
                continue;
            }

            $this->command?->info(sprintf(
                'Created InternalProcedure [%s] under [%s].',
                $form->value,
                $parent->name,
            ));
        }
    }
}
