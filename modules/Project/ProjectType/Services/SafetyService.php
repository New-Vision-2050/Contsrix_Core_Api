<?php

namespace Modules\Project\ProjectType\Services;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\SafetyRecord;
use Modules\Project\ProjectType\Models\Violation;
use Modules\Project\ProjectType\Repositories\SafetyRecordRepository;

class SafetyService
{
    public function __construct(private SafetyRecordRepository $repository) {}

    public function list(string $projectId): Collection
    {
        $records = SafetyRecord::where('project_id', $projectId)
            ->with(['violations', 'morphable'])
            ->orderBy('created_at', 'desc')
            ->get();

        $allViolations = Violation::orderBy('code')->get();

        return $records->each(function ($record) use ($allViolations) {
            $record->setRelation('all_violations', $this->buildAllViolations($record, $allViolations));
        });
    }

    public function show(string $id): SafetyRecord
    {
        $record = $this->repository->findOneOrFail($id);
        $record->load(['violations', 'morphable']);
        $allViolations = Violation::orderBy('code')->get();
        $record->setRelation('all_violations', $this->buildAllViolations($record, $allViolations));
        return $record;
    }

    public function create(array $data): SafetyRecord
    {
        $record = $this->repository->create(Arr::only($data, [
            'project_id',
            'morphable_type',
            'morphable_id',
            'order_type',
            'date',
            'time',
            'required_score',
            'earned_score',
            'percentage',
            'consultant_engineer',
            'consultant',
            'contractor_id',
        ]));

        $this->syncViolations($record, Arr::get($data, 'violations', []));
        return $this->show($record->id);
    }

    public function update(string $id, array $data): SafetyRecord
    {
        $record = $this->repository->findOneOrFail($id);
        $record->update(Arr::only($data, [
            'morphable_type',
            'morphable_id',
            'order_type',
            'date',
            'time',
            'required_score',
            'earned_score',
            'percentage',
            'consultant_engineer',
            'consultant',
            'contractor_id',
        ]));

        if (Arr::has($data, 'violations')) {
            $this->syncViolations($record, Arr::get($data, 'violations', []));
        }

        return $this->show($record->id);
    }

    public function delete(string $id): bool
    {
        $record = $this->repository->findOneOrFail($id);
        return $record->delete();
    }

    private function buildAllViolations(SafetyRecord $record, Collection $allViolations): Collection
    {
        $attached = $record->violations->keyBy('id');

        return $allViolations->map(function ($violation) use ($attached) {
            $isAttached = $attached->has($violation->id);
            $pivot = $isAttached ? $attached->get($violation->id)->pivot : null;

            return [
                'id' => $violation->id,
                'code' => $violation->code,
                'description' => $violation->description,
                'category' => $violation->category,
                'is_attached' => $isAttached,
                'weight' => $pivot?->weight,
            ];
        });
    }

    private function syncViolations(SafetyRecord $record, array $violations): void
    {
        $syncData = [];
        foreach ($violations as $v) {
            if (isset($v['violation_id'])) {
                $syncData[$v['violation_id']] = ['weight' => $v['weight'] ?? null];
            }
        }
        $record->violations()->sync($syncData);
    }
}
