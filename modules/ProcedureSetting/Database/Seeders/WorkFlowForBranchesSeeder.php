<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Models\WorkFlow;

class WorkFlowForBranchesSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('work_flows') || ! Schema::hasTable('management_hierarchy_work_flow')) {
            $this->command?->warn('Work flow tables are missing. Run ProcedureSetting migrations first.');

            return;
        }
        if (! Schema::hasColumn('work_flows', 'type')) {
            $this->command?->warn('Column [work_flows.type] is missing. Run the latest ProcedureSetting migrations first.');

            return;
        }

        if (ManagementHierarchy::query()->where('type', 'branch')->whereNotNull('company_id')->doesntExist()) {
            $this->command?->info('No branches found (management_hierarchies.type = branch). Nothing to seed.');

            return;
        }

        DB::transaction(function (): void {
            $groups = ManagementHierarchy::query()
                ->where('type', 'branch')
                ->whereNotNull('company_id')
                ->orderBy('company_id')
                ->get()
                ->groupBy('company_id');

            foreach ($groups as $companyId => $branches) {
                $branchIds = $branches->pluck('id')->filter()->values()->all();
                foreach (ProcedureSettingType::cases() as $type) {
                    $workFlowId = DB::table('work_flows')
                        ->where('company_id', (string) $companyId)
                        ->where('name', 'default')
                        ->where('type', $type->value)
                        ->value('id');

                    if (! is_string($workFlowId) || $workFlowId === '') {
                        $workFlowId = (string) Str::uuid();
                        $now = now();

                        DB::table('work_flows')->insert([
                            'id'         => $workFlowId,
                            'company_id' => (string) $companyId,
                            'name'       => 'default',
                            'type'       => $type->value,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    $workFlow = WorkFlow::query()->find($workFlowId);
                    if (! $workFlow) {
                        continue;
                    }

                    $workFlow->managementHierarchies()->syncWithoutDetaching($branchIds);

                    $this->command?->info(sprintf(
                        'WorkFlow [%s] (%s) linked to %d branch(es) for company [%s].',
                        $workFlow->id,
                        $type->value,
                        count($branchIds),
                        $companyId
                    ));
                }
            }
        });

        $this->command?->info('WorkFlowForBranchesSeeder finished.');
    }
}
