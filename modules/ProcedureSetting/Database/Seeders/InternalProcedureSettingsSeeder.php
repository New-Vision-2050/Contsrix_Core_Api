<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;

/**
 * Seeds default InternalProcedureSettings (child procedure_settings with form set)
 * for every existing parent ProcedureSetting.
 *
 * Run after migrations and after parent ProcedureSettings already exist.
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

        $parents = ProcedureSetting::query()
            ->whereNull('parent_id')
            ->get();

        if ($parents->isEmpty()) {
            $this->command?->info('No parent ProcedureSettings found. Nothing to seed.');
            return;
        }

        foreach ($parents as $parent) {
            $this->seedForParent($parent);
        }

        $this->command?->info('InternalProcedureSettingsSeeder finished.');
    }

    private function seedForParent(ProcedureSetting $parent): void
    {
        $type = $parent->type;

        $forms = InternalProcessForm::forType($type);

        if ($forms === []) {
            return;
        }

        foreach ($forms as $order => $form) {
            $alreadyExists = ProcedureSetting::query()
                ->where('parent_id', $parent->id)
                ->where('form', $form->value)
                ->exists();

            if ($alreadyExists) {
                $this->command?->info(sprintf(
                    'InternalProcedure [%s] already exists under [%s]. Skipping.',
                    $form->value,
                    $parent->name,
                ));
                continue;
            }

            ProcedureSetting::query()->create([
                'id'          => (string) Str::uuid(),
                'company_id'  => $parent->company_id,
                'parent_id'   => $parent->id,
                'name'        => $form->labelAr(),
                'form'        => $form->value,
                'type'        => $type,
                'execute_type'=> 'sequence',
                'sort_order'  => $order,
                'conditions'  => InternalProcessCondition::defaultValuesForForm($form),
                'percentage'  => 0,
            ]);

            $this->command?->info(sprintf(
                'Created InternalProcedure [%s] under [%s].',
                $form->value,
                $parent->name,
            ));
        }
    }
}
