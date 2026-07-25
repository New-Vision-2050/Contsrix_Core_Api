<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;
use Modules\ProcedureSetting\Support\ProcedureSettingProjectResolver;

trait ValidatesProjectEmployeeSelections
{
    protected function validateProjectEmployeeSelections(Validator $validator): void
    {
        if ($validator->errors()->has('project_employee_ids')) {
            return;
        }

        $projectEmployeeIds = collect($this->input('project_employee_ids', []))
            ->map(static fn (mixed $id): string => (string) $id)
            ->filter(static fn (string $id): bool => $id !== '')
            ->unique()
            ->values()
            ->all();

        if ($projectEmployeeIds === []) {
            return;
        }

        $projectId = ProcedureSettingProjectResolver::projectIdForProcedureSetting(
            (string) $this->route('procedureSettingId')
        );
        if ($projectId === null) {
            $validator->errors()->add(
                'project_employee_ids',
                'Project employee selections require a project procedure setting.'
            );

            return;
        }

        $matchingProjectEmployeeIds = DB::table('project_employees')
            ->where('project_id', $projectId)
            ->whereIn('id', $projectEmployeeIds)
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();

        if (array_diff($projectEmployeeIds, $matchingProjectEmployeeIds) === []) {
            return;
        }

        $validator->errors()->add(
            'project_employee_ids',
            'Selected project employees must belong to this project.'
        );
    }
}
