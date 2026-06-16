<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('work_flows')) {
            return;
        }

        $this->deduplicateWorkFlows();

        if (! Schema::hasColumn('work_flows', 'company_id')
            || ! Schema::hasColumn('work_flows', 'name')
            || ! Schema::hasColumn('work_flows', 'type')
        ) {
            return;
        }

        $constraintExists = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND CONSTRAINT_NAME = ?
             AND CONSTRAINT_TYPE = ?',
            ['work_flows', 'work_flows_company_name_type_unique', 'UNIQUE']
        );

        if (count($constraintExists) === 0) {
            Schema::table('work_flows', function (Blueprint $table): void {
                $table->unique(['company_id', 'name', 'type'], 'work_flows_company_name_type_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('work_flows')) {
            return;
        }

        $constraintExists = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND CONSTRAINT_NAME = ?
             AND CONSTRAINT_TYPE = ?',
            ['work_flows', 'work_flows_company_name_type_unique', 'UNIQUE']
        );

        if (count($constraintExists) > 0) {
            Schema::table('work_flows', function (Blueprint $table): void {
                $table->dropUnique('work_flows_company_name_type_unique');
            });
        }
    }

    private function deduplicateWorkFlows(): void
    {
        $duplicateGroups = DB::table('work_flows')
            ->select('company_id', 'name', 'type')
            ->whereNotNull('company_id')
            ->groupBy('company_id', 'name', 'type')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $workFlowIds = DB::table('work_flows')
                ->where('company_id', $group->company_id)
                ->where('name', $group->name)
                ->where('type', $group->type)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->pluck('id');

            $keepId = $workFlowIds->first();
            $deleteIds = $workFlowIds->slice(1)->values()->all();

            if ($deleteIds === []) {
                continue;
            }

            // Remap procedure_settings foreign keys
            DB::table('procedure_settings')
                ->whereIn('work_flow_id', $deleteIds)
                ->update(['work_flow_id' => $keepId]);

            // Collect management hierarchies linked to duplicates
            $mhIds = DB::table('management_hierarchy_work_flow')
                ->whereIn('work_flow_id', $deleteIds)
                ->pluck('management_hierarchy_id')
                ->unique()
                ->values()
                ->all();

            // Link them to the kept workflow (ignore if already linked)
            if ($mhIds !== []) {
                $now = now();
                $inserts = [];
                foreach ($mhIds as $mhId) {
                    $inserts[] = [
                        'management_hierarchy_id' => $mhId,
                        'work_flow_id'            => $keepId,
                        'created_at'              => $now,
                        'updated_at'              => $now,
                    ];
                }
                DB::table('management_hierarchy_work_flow')->insertOrIgnore($inserts);
            }

            // Remove old pivot records for duplicates
            DB::table('management_hierarchy_work_flow')
                ->whereIn('work_flow_id', $deleteIds)
                ->delete();

            // Remove duplicate workflows
            DB::table('work_flows')
                ->whereIn('id', $deleteIds)
                ->delete();
        }
    }
};
