<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;
use Modules\Project\ProjectManagement\Models\ProjectManagement;

trait ValidatesReceiverCompanyActionTakers
{
    protected function validateReceiverCompanyActionTakers(Validator $validator): void
    {
        if ($this->input('action_taker_type') !== 'receiver_company') {
            return;
        }

        if ($validator->errors()->has('receiver_company_ids')) {
            return;
        }

        $receiverCompanyIds = collect($this->input('receiver_company_ids', []))
            ->map(static fn (mixed $id): string => (string) $id)
            ->filter(static fn (string $id): bool => $id !== '')
            ->unique()
            ->values()
            ->all();

        if ($receiverCompanyIds === []) {
            return;
        }

        $projectId = $this->projectIdForProcedureSetting((string) $this->route('procedureSettingId'));
        if ($projectId === null) {
            $validator->errors()->add(
                'receiver_company_ids',
                'Receiver company action takers require a project procedure setting.'
            );

            return;
        }

        $project = DB::table('projects')
            ->where('id', $projectId)
            ->first(['id', 'company_id']);

        if ($project === null || $project->company_id === null) {
            $validator->errors()->add('receiver_company_ids', 'The selected project does not exist.');

            return;
        }

        $acceptedCompanyIds = DB::table('resource_shares')
            ->where('shareable_type', ProjectManagement::class)
            ->where('shareable_id', $projectId)
            ->where('owner_company_id', (string) $project->company_id)
            ->where('status', 'accepted')
            ->whereIn('shared_with_company_id', $receiverCompanyIds)
            ->pluck('shared_with_company_id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();

        if (array_diff($receiverCompanyIds, $acceptedCompanyIds) === []) {
            return;
        }

        $validator->errors()->add(
            'receiver_company_ids',
            'Selected receiver companies must be accepted shared companies for this project.'
        );
    }

    private function projectIdForProcedureSetting(string $procedureSettingId): ?string
    {
        if ($procedureSettingId === '') {
            return null;
        }

        $projectId = DB::table('procedure_settings')
            ->join('work_flows', 'work_flows.id', '=', 'procedure_settings.work_flow_id')
            ->where('procedure_settings.id', $procedureSettingId)
            ->value('work_flows.project_id');

        return is_string($projectId) && $projectId !== '' ? $projectId : null;
    }
}
