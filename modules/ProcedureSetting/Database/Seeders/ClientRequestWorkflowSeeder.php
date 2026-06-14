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

class ClientRequestWorkflowSeeder extends Seeder
{
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
            $this->seedProcedureSettingForCompany((string) $companyId, ProcedureSettingType::ClientRequest);
        }

        $this->command?->info('ClientRequestWorkflowSeeder finished.');
    }

    private function seedProcedureSettingForCompany(string $companyId, ProcedureSettingType $type): void
    {
        $workFlow = WorkFlow::defaultForCompany($companyId, $type->value);

        $exists = ProcedureSetting::query()
            ->where('company_id', $companyId)
            ->where('type', $type->value)
            ->where('work_flow_id', $workFlow->id)
            ->exists();

        if ($exists) {
            $this->command?->info(sprintf(
                'ProcedureSetting [%s] already exists for company [%s]. Skipping.',
                $type->value,
                $companyId,
            ));

            return;
        }

        ProcedureSetting::query()->create([
            'id'           => (string) Str::uuid(),
            'company_id'   => $companyId,
            'work_flow_id' => $workFlow->id,
            'name'         => 'Client Request',
            'type'         => $type->value,
            'execute_type' => 'sequence',
            'sort_order'   => 0,
            'percentage'   => 0,
        ]);

        $this->command?->info(sprintf(
            'Created ProcedureSetting [%s] for company [%s].',
            $type->value,
            $companyId,
        ));
    }
}
