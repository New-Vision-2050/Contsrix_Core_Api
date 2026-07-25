<?php

namespace Modules\Project\ProjectType\Services;

use App\Exceptions\CustomException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectType\Events\SafetyTaskAssigned as SafetyTaskAssignedEvent;
use Modules\Project\ProjectType\Exceptions\SafetyException;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectType\Models\SafetyRecord;
use Modules\Project\ProjectType\Models\Violation;
use Modules\Project\ProjectType\Notifications\SafetyTaskAssigned as SafetyTaskAssignedNotification;
use Modules\Project\ProjectType\Repositories\SafetyRecordRepository;
use Modules\User\Models\User;

class SafetyService
{
    public function __construct(private SafetyRecordRepository $repository) {}

    public function list(string $projectId): Collection
    {
        $records = SafetyRecord::query()
            ->where('project_id', $projectId)
            ->with(['violations', 'morphable', 'assignedUser'])
            ->orderByDesc('created_at')
            ->get();

        return $this->attachAllViolations($records);
    }

    public function inbox(string $userId): Collection
    {
        $records = SafetyRecord::query()
            ->where('assigned_user_id', $userId)
            ->where('status', 'pending')
            ->with(['violations', 'morphable', 'assignedUser', 'project'])
            ->orderByDesc('created_at')
            ->get();

        return $this->attachAllViolations($records);
    }

    public function show(string $projectId, string $id): SafetyRecord
    {
        $record = $this->findForProject($projectId, $id);
        $record->load(['violations', 'morphable', 'assignedUser']);

        return $this->attachAllViolationsToRecord($record);
    }

    public function create(array $data): array
    {
        $assignedUserIds = array_values(array_filter(
            Arr::wrap(Arr::get($data, 'assigned_user_ids', Arr::get($data, 'assigned_user_id')))
        ));

        if ($assignedUserIds === []) {
            throw SafetyException::assigneeRequired();
        }

        $projectId = $data['project_id'] ?? null;
        if (! $projectId) {
            throw new CustomException('project_id is required.', 422);
        }

        $this->assertMorphableBelongsToProject(
            (string) ($data['morphable_type'] ?? ''),
            (string) ($data['morphable_id'] ?? ''),
            $projectId
        );

        return DB::transaction(function () use ($data, $assignedUserIds, $projectId) {
            $records = [];
            $allViolations = Violation::query()->orderBy('code')->get();

            $validEmployeeIds = ProjectEmployee::query()
                ->where('project_id', $projectId)
                ->whereIn('user_id', $assignedUserIds)
                ->pluck('user_id')
                ->map(fn ($id) => (string) $id)
                ->all();

            foreach ($assignedUserIds as $userId) {
                if (! in_array((string) $userId, $validEmployeeIds, true)) {
                    throw SafetyException::notProjectEmployee((string) $userId);
                }

                $record = $this->repository->create([
                    'project_id' => $projectId,
                    'morphable_type' => $data['morphable_type'],
                    'morphable_id' => (string) $data['morphable_id'],
                    'order_type' => $data['order_type'] ?? null,
                    'date' => $data['date'] ?? null,
                    'time' => $data['time'] ?? null,
                    'required_score' => $data['required_score'] ?? null,
                    'earned_score' => $data['earned_score'] ?? null,
                    'percentage' => $data['percentage'] ?? null,
                    'consultant_engineer' => $data['consultant_engineer'] ?? null,
                    'consultant' => $data['consultant'] ?? null,
                    'contractor_id' => $data['contractor_id'] ?? null,
                    'assigned_user_id' => $userId,
                    'status' => 'pending',
                ]);

                $this->syncViolations($record, Arr::get($data, 'violations', []));
                $record->load(['violations', 'morphable', 'assignedUser']);
                $record->setRelation('all_violations', $this->buildAllViolations($record, $allViolations));

                $user = User::withoutGlobalScopes()->find($userId);
                if ($user) {
                    $user->notify(new SafetyTaskAssignedNotification($record));
                    event(new SafetyTaskAssignedEvent($record));
                }

                $records[] = $record;
            }

            return $records;
        });
    }

    public function update(string $projectId, string $id, array $data): SafetyRecord
    {
        $record = $this->findForProject($projectId, $id);

        if ($record->status === 'completed') {
            throw SafetyException::cannotModifyCompleted();
        }

        $record->update(Arr::only($data, [
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

        return $this->show($projectId, $record->id);
    }

    public function evaluateViolations(string $projectId, string $id, array $violations, ?string $actorUserId = null): SafetyRecord
    {
        $record = $this->findForProject($projectId, $id);

        if ($record->status === 'completed') {
            throw SafetyException::cannotEvaluateCompleted();
        }

        $actorUserId = $actorUserId ?? auth()->id();
        if ((string) $record->assigned_user_id !== (string) $actorUserId) {
            throw SafetyException::notAuthorizedToEvaluate();
        }

        $this->syncViolations($record, $violations);
        $record->update(['status' => 'completed']);

        return $this->show($projectId, $record->id);
    }

    public function delete(string $projectId, string $id): bool
    {
        $record = $this->findForProject($projectId, $id);

        if ($record->status === 'completed') {
            throw SafetyException::cannotDeleteCompleted();
        }

        return (bool) $record->delete();
    }

    private function findForProject(string $projectId, string $id): SafetyRecord
    {
        $record = SafetyRecord::query()
            ->where('project_id', $projectId)
            ->where('id', $id)
            ->first();

        if (! $record) {
            throw SafetyException::notFound();
        }

        return $record;
    }

    private function assertMorphableBelongsToProject(string $type, string $morphableId, string $projectId): void
    {
        $exists = match ($type) {
            'project_notification' => ProjectNotification::withoutGlobalScopes()
                ->where('id', $morphableId)
                ->where('project_id', $projectId)
                ->exists(),
            'project_order_permit' => ProjectOrderPermit::query()
                ->where('id', $morphableId)
                ->where('project_id', $projectId)
                ->exists(),
            default => false,
        };

        if (! $exists) {
            throw SafetyException::invalidMorphable();
        }
    }

    private function attachAllViolations(Collection $records): Collection
    {
        if ($records->isEmpty()) {
            return $records;
        }

        $allViolations = Violation::query()->orderBy('code')->get();

        return $records->each(function (SafetyRecord $record) use ($allViolations) {
            $record->setRelation('all_violations', $this->buildAllViolations($record, $allViolations));
        });
    }

    private function attachAllViolationsToRecord(SafetyRecord $record): SafetyRecord
    {
        $allViolations = Violation::query()->orderBy('code')->get();
        $record->setRelation('all_violations', $this->buildAllViolations($record, $allViolations));

        return $record;
    }

    private function buildAllViolations(SafetyRecord $record, Collection $allViolations): Collection
    {
        $attached = $record->relationLoaded('violations')
            ? $record->violations->keyBy('id')
            : collect();

        return $allViolations->map(function (Violation $violation) use ($attached) {
            $isAttached = $attached->has($violation->id);
            $pivot = $isAttached ? $attached->get($violation->id)->pivot : null;

            return [
                'id' => $violation->id,
                'code' => $violation->code,
                'description' => $violation->description,
                'category' => $violation->category,
                'is_attached' => $isAttached,
                'weight' => $pivot?->weight,
                'status' => $pivot?->status,
            ];
        });
    }

    private function syncViolations(SafetyRecord $record, array $violations): void
    {
        $syncData = [];

        foreach ($violations as $violation) {
            if (! isset($violation['violation_id'])) {
                continue;
            }

            $syncData[$violation['violation_id']] = [
                'weight' => $violation['weight'] ?? null,
                'status' => $violation['status'] ?? null,
            ];
        }

        $record->violations()->sync($syncData);
    }
}
