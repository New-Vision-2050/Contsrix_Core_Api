<?php

namespace Modules\Project\ProjectType\Services;

use App\Exceptions\CustomException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectType\Events\SafetyTaskAssigned as SafetyTaskAssignedEvent;
use Modules\Project\ProjectType\Exceptions\SafetyException;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectType\Models\SafetyRecord;
use Modules\Project\ProjectType\Models\Violation;
use Modules\Project\ProjectType\Notifications\SafetyTaskAssigned as SafetyTaskAssignedNotification;
use Illuminate\Http\UploadedFile;
use Modules\Project\ProjectType\Repositories\SafetyRecordRepository;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\User\Models\User;
use Throwable;

class SafetyService
{
    public function __construct(
        private SafetyRecordRepository $repository,
        private FileUploadService $fileUploadService,
    ) {}

    public function list(string $projectId, array $filters = []): EloquentCollection
    {
        $records = SafetyRecord::query()
            ->where('project_id', $projectId)
            ->with(['violations', 'morphable', 'assignedUser', 'contractor', 'media'])
            ->filter($filters)
            ->orderByDesc('created_at')
            ->get();

        return $this->attachAllViolations($records);
    }

    public function inbox(string $userId, array $filters = []): EloquentCollection
    {
        // Inbox is always scoped to the authenticated user; don't let
        // assigned_user_id override that constraint.
        unset($filters['assigned_user_id']);

        $records = SafetyRecord::query()
            ->where('assigned_user_id', $userId)
            ->with(['violations', 'morphable', 'assignedUser', 'contractor', 'project', 'media'])
            ->filter($filters)
            ->orderByDesc('created_at')
            ->get();

        return $this->attachAllViolations($records);
    }

    public function report(string $projectId, array $filters = []): Collection
    {
        $records = SafetyRecord::query()
            ->where('project_id', $projectId)
            ->with(['morphable', 'contractor'])
            ->filter($filters)
            ->get();

        return $records
            ->groupBy(fn (SafetyRecord $r) => $r->morphable_type.'|'.$r->morphable_id)
            ->map(function (Collection $group) {
                /** @var SafetyRecord $first */
                $first = $group->first();
                $total = $group->count();
                $completed = $group->where('status', 'completed')->count();
                $pending = $group->where('status', 'pending')->count();

                if ($completed === 0) {
                    $status = 'متأخر';
                } elseif ($completed >= $total) {
                    $status = 'مكتمل';
                } else {
                    $status = 'جارية';
                }

                return [
                    'morphable_type' => $first->morphable_type,
                    'morphable_id' => $first->morphable_id,
                    'morphable_display' => $first->morphable?->name
                        ?? $first->morphable?->notification_number
                        ?? null,
                    'contractor_id' => $first->contractor_id,
                    'contractor_name' => $first->contractor?->name,
                    'consultant_engineer' => $first->consultant_engineer,
                    'consultant' => $first->consultant,
                    'total_assignments' => $total,
                    'completed_count' => $completed,
                    'pending_count' => $pending,
                    'status' => $status,
                ];
            })
            ->values();
    }

    public function show(string $projectId, string $id): SafetyRecord
    {
        $record = $this->findForProject($projectId, $id);
        $record->load(['violations', 'morphable', 'assignedUser', 'contractor', 'media']);

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
                $record->load(['violations', 'morphable', 'assignedUser', 'contractor', 'media']);
                $record->setRelation('all_violations', $this->buildAllViolations($record, $allViolations));

                $user = User::withoutGlobalScopes()->find($userId);
                if (! $user) {
                    throw SafetyException::notProjectEmployee((string) $userId);
                }

                $dispatch = function () use ($user, $record) {
                    if (Schema::hasTable('notifications')) {
                        $user->notify(new SafetyTaskAssignedNotification($record));
                    }
                    event(new SafetyTaskAssignedEvent($record));
                };

                // afterCommit is skipped under PHPUnit because DatabaseTransactions
                // never reaches transaction level 0 (it rolls back).
                if (app()->runningUnitTests()) {
                    $dispatch();
                } else {
                    DB::afterCommit($dispatch);
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

    /**
     * @param  array<int, array{violation_id: string, weight?: mixed, status?: string, images?: UploadedFile[]}>  $violations
     */
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
        $this->uploadViolationEvidence($record, $violations);
        $this->calculateAndStoreScores($record);

        return $this->show($projectId, $record->id);
    }

    /**
     * Auto-create a pending SafetyRecord for each assigned user of a
     * ProjectNotification, one per user. Users that already have a record for
     * this notification are skipped, so publishing and updating only ever
     * produce tasks for users who don't have one yet.
     *
     * Called from ProjectNotificationService::publishNotification and
     * publishDraft, and for newly assigned users in updatePublished.
     */
    public function createFromNotification(ProjectNotification $notification): void
    {
        $this->createForNotificationUsers(
            $notification,
            $notification->assigned_user_ids ?? [],
        );
    }

    /**
     * Handle safety records when a notification task is reassigned:
     * 1. Delete pending safety records for users no longer assigned.
     * 2. Create a fresh safety record for every current target user.
     */
    public function reassignFromNotification(
        ProjectNotification $notification,
        array $newTargetUserIds,
    ): void {
        if (empty($newTargetUserIds)) {
            return;
        }

        $newTargetUserIds = array_values(array_unique(array_map('strval', $newTargetUserIds)));

        // Users dropped from the assignment should no longer see the task in
        // their inbox. Their completed records are kept as an audit trail.
        try {
            SafetyRecord::query()
                ->where('morphable_type', 'project_notification')
                ->where('morphable_id', (string) $notification->id)
                ->where('status', 'pending')
                ->whereNotIn('assigned_user_id', $newTargetUserIds)
                ->delete();
        } catch (Throwable $e) {
            Log::warning('Failed to prune safety records for reassigned project notification.', [
                'project_notification_id' => $notification->id,
                'exception' => $e->getMessage(),
            ]);
        }

        // A reassignment is a new work order, so every target user gets a new
        // task even if they already hold one for this notification. Earlier
        // records are left untouched so the full assignment history survives.
        $this->createForNotificationUsers($notification, $newTargetUserIds, skipExisting: false);
    }

    /**
     * Shared auto-creation path for notification-driven safety records.
     *
     * Safety records are a side effect of publishing/reassigning a
     * notification, so this must never abort the caller: users that cannot
     * legitimately receive a record are dropped, and anything unexpected is
     * logged instead of thrown.
     */
    private function createForNotificationUsers(
        ProjectNotification $notification,
        array $userIds,
        bool $skipExisting = true,
    ): void {
        $projectId = (string) ($notification->project_id ?? '');

        if ($userIds === [] || $projectId === '') {
            return;
        }

        $userIds = array_values(array_unique(array_map('strval', array_filter($userIds))));
        $toCreate = $this->projectEmployeeIds($projectId, $userIds);

        if ($skipExisting) {
            $toCreate = array_values(array_diff(
                $toCreate,
                $this->userIdsWithRecord($notification, $userIds),
            ));
        }

        if ($toCreate === []) {
            return;
        }

        try {
            $this->create([
                'project_id' => $projectId,
                'morphable_type' => 'project_notification',
                'morphable_id' => (string) $notification->id,
                'assigned_user_ids' => $toCreate,
                'date' => $notification->task_date?->toDateString(),
                'time' => $notification->task_time?->format('H:i'),
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to auto-create safety records for project notification.', [
                'project_notification_id' => $notification->id,
                'assigned_user_ids' => $toCreate,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Which of the given users already hold a safety record for this
     * notification, in any status. Completed records count, so a user who
     * finished their evaluation is not handed the same task twice.
     *
     * @return list<string>
     */
    private function userIdsWithRecord(ProjectNotification $notification, array $userIds): array
    {
        return SafetyRecord::query()
            ->where('morphable_type', 'project_notification')
            ->where('morphable_id', (string) $notification->id)
            ->whereIn('assigned_user_id', $userIds)
            ->pluck('assigned_user_id')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Narrow a set of user ids down to those actually assigned to the project.
     *
     * Notification assignment only requires the user to exist, while a safety
     * record requires project membership, so the difference is dropped rather
     * than rejected.
     *
     * @return list<string>
     */
    private function projectEmployeeIds(string $projectId, array $userIds): array
    {
        return ProjectEmployee::query()
            ->where('project_id', $projectId)
            ->whereIn('user_id', $userIds)
            ->pluck('user_id')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
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

    private function attachAllViolations(EloquentCollection $records): EloquentCollection
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

    private function buildAllViolations(SafetyRecord $record, EloquentCollection $allViolations): Collection
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
        $defaults = Violation::query()
            ->whereIn('id', collect($violations)->pluck('violation_id')->filter()->all())
            ->pluck('default_weight', 'id');

        foreach ($violations as $violation) {
            if (! isset($violation['violation_id'])) {
                continue;
            }

            $violationId = (string) $violation['violation_id'];
            $status = (string) ($violation['status'] ?? '');
            $baseWeight = abs((float) ($violation['weight'] ?? $defaults[$violationId] ?? 0));

            $syncData[$violationId] = [
                'weight' => $this->signedWeight($baseWeight, $status),
                'status' => $status,
            ];
        }

        $record->violations()->sync($syncData);
    }

    /**
     * Persist earned_score, required_score, and percentage from pivot weights.
     *
     * earned_score    = sum of signed pivot weights (N/A stored as 0)
     * required_score  = sum of ABS(weights) for non-N/A (max if all were no_violation)
     * percentage      = earned_score / required_score * 100 (100 when required_score = 0)
     */
    private function calculateAndStoreScores(SafetyRecord $record): void
    {
        $record->load('violations');

        $earnedScore = 0.0;
        $requiredScore = 0.0;

        foreach ($record->violations as $violation) {
            $status = (string) ($violation->pivot->status ?? '');
            $weight = (float) ($violation->pivot->weight ?? 0);

            if ($status === 'not_applicable') {
                continue;
            }

            $earnedScore += $weight;
            $requiredScore += abs($weight);
        }

        $percentage = $requiredScore == 0.0
            ? 100.0
            : round(($earnedScore / $requiredScore) * 100, 2);

        $record->update([
            'status' => 'completed',
            'earned_score' => round($earnedScore, 2),
            'required_score' => round($requiredScore, 2),
            'percentage' => $percentage,
        ]);
    }

    private function signedWeight(float $baseWeight, string $status): float
    {
        return match ($status) {
            'violation_found' => -1 * $baseWeight,
            'no_violation' => $baseWeight,
            'not_applicable' => 0.0,
            default => 0.0,
        };
    }

    /**
     * Upload up to 3 evidence images per violation into the shared
     * `violation_evidence` collection, tagged with the violation_id custom property.
     *
     * @param  array<int, array{violation_id?: string, images?: UploadedFile[]}>  $violations
     */
    private function uploadViolationEvidence(SafetyRecord $record, array $violations): void
    {
        foreach ($violations as $violation) {
            $violationId = $violation['violation_id'] ?? null;
            $images = array_values(array_filter(
                Arr::wrap($violation['images'] ?? []),
                fn ($file) => $file instanceof UploadedFile
            ));

            if (! $violationId || $images === []) {
                continue;
            }

            $mediaItems = $this->fileUploadService->uploadFile(
                $record,
                $images,
                filePath: 'safety/violation-evidence/'.$violationId,
                collectionName: 'violation_evidence',
            );

            foreach ($mediaItems as $media) {
                $media->setCustomProperty('violation_id', (string) $violationId);
                $media->save();
            }
        }
    }
    // }
}
