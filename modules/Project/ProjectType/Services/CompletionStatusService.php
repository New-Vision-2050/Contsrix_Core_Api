<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ConnectionCompletionPhase;
use Modules\Project\ProjectType\Models\ConnectionPhaseStatus;
use Modules\Project\ProjectType\Models\ProjectCompletionPhase;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectType\Models\ProjectPhaseStatus;
use Modules\Project\ProjectType\Presenters\CompletionStatusPresenter;

class CompletionStatusService
{
    public function listProjectPhases(): Collection
    {
        return ProjectCompletionPhase::with(['statuses', 'department'])->get();
    }

    public function listConnectionPhases(): Collection
    {
        return ConnectionCompletionPhase::with(['statuses', 'department'])->get();
    }

    public function listProjectStatuses(array $filters = []): Collection
    {
        $query = ProjectPhaseStatus::with('phase');

        if (! empty($filters['project_completion_phase_id'])) {
            $query->where('project_completion_phase_id', $filters['project_completion_phase_id']);
        }

        return $query->get();
    }

    public function listConnectionStatuses(array $filters = []): Collection
    {
        $query = ConnectionPhaseStatus::with('phase');

        if (! empty($filters['connection_completion_phase_id'])) {
            $query->where('connection_completion_phase_id', $filters['connection_completion_phase_id']);
        }

        return $query->get();
    }

    public function getCompletionDataForOrderPermit(string $orderPermitId): array
    {
        $orderPermit = ProjectOrderPermit::with('department')->findOrFail($orderPermitId);
        $departmentName = $orderPermit->department?->name;

        if ($departmentName === 'مشاريع') {
            $phases = ProjectCompletionPhase::with(['statuses', 'department'])->get();
        } elseif ($departmentName === 'توصيلات') {
            $phases = ConnectionCompletionPhase::with(['statuses', 'department'])->get();
        } else {
            $phases = collect();
        }

        return [
            'order_permit' => [
                'id' => $orderPermit->id,
                'department_id' => $orderPermit->order_permit_department_id,
                'department_name' => $departmentName,
            ],
            'completion_phases' => CompletionStatusPresenter::collection($phases),
        ];
    }
}
