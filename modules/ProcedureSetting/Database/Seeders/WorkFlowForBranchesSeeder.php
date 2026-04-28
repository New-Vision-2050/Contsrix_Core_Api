<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\ProcedureSetting\Models\WorkFlow;

class WorkFlowForBranchesSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('work_flows') || ! Schema::hasTable('management_hierarchy_work_flow')) {
            $this->command?->warn('Work flow tables are missing. Run ProcedureSetting migrations first.');

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
                $workFlow = WorkFlow::defaultForCompany((string) $companyId);

                $branchIds = $branches->pluck('id')->filter()->values()->all();

                $workFlow->managementHierarchies()->syncWithoutDetaching($branchIds);

                $this->command?->info(sprintf(
                    'WorkFlow [%s] linked to %d branch(es) for company [%s].',
                    $workFlow->id,
                    count($branchIds),
                    $companyId
                ));
            }
        });

        $this->command?->info('WorkFlowForBranchesSeeder finished.');
    }
}
