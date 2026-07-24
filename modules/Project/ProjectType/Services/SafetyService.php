<?php

namespace Modules\Project\ProjectType\Services;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectType\Models\SafetyRecord;
use Modules\Project\ProjectType\Models\Violation;
use Modules\Project\ProjectType\Repositories\SafetyRecordRepository;
use App\Notifications\SafetyTaskAssigned as SafetyTaskAssignedNotification;
use Modules\Project\ProjectType\Events\SafetyTaskAssigned as SafetyTaskAssignedEvent;
use Modules\User\Models\User;

class SafetyService
{
    public function __construct(private SafetyRecordRepository $repository) {}

    public function list(string $projectId): Collection
    {
        $records = SafetyRecord::where('project_id', $projectId)
            ->with(['violations', 'morphable', 'assignedUser'])
            ->orderBy('created_at', 'desc')
            ->get();

        $allViolations = Violation::orderBy('code')->get();

        return $records->each(function ($record) use ($allViolations) {
            $record->setRelation('all_violations', $this->buildAllViolations($record, $allViolations));
        });
    }

    public function inbox(string $userId): Collection
    {
        $records = SafetyRecord::where('assigned_user_id', $userId)
            ->where('status', 'pending')
            ->with(['violations', 'morphable', 'assignedUser'])
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
        $record->load(['violations', 'morphable', 'assignedUser']);
        $allViolations = Violation::orderBy('code')->get();
        $record->setRelation('all_violations', $this->buildAllViolations($record, $allViolations));
        return $record;
    }

    /**
     * إنشاء سجل سلامة واحد أو عدة سجلات (إذا أُرسلت assigned_user_ids مصفوفة).
     */
    public function create(array $data): array
    {
        $assignedUserIds = Arr::get($data, 'assigned_user_ids', [Arr::get($data, 'assigned_user_id')]);
        if (empty($assignedUserIds)) {
            throw new \Exception('يجب تعيين مستخدم واحد على الأقل.');
        }

        $projectId = $data['project_id'] ?? null;

        $records = [];
        foreach ($assignedUserIds as $userId) {
            if ($projectId) {
                $isEmployee = ProjectEmployee::where('project_id', $projectId)
                    ->where('user_id', $userId)
                    ->exists();
                if (!$isEmployee) {
                    throw new \Exception("المستخدم {$userId} ليس موظفاً في هذا المشروع.");
                }
            }

            $record = $this->repository->create([
                'project_id'      => $projectId,
                'morphable_type'  => $data['morphable_type'] ?? null,
                'morphable_id'    => $data['morphable_id'] ?? null,
                'order_type'      => $data['order_type'] ?? null,
                'date'            => $data['date'] ?? null,
                'time'            => $data['time'] ?? null,
                'required_score'  => $data['required_score'] ?? null,
                'earned_score'    => $data['earned_score'] ?? null,
                'percentage'      => $data['percentage'] ?? null,
                'consultant_engineer' => $data['consultant_engineer'] ?? null,
                'consultant'      => $data['consultant'] ?? null,
                'contractor_id'   => $data['contractor_id'] ?? null,
                'assigned_user_id' => $userId,
                'status'          => 'pending',
            ]);

            $this->syncViolations($record, Arr::get($data, 'violations', []));
            $records[] = $record;

            $user = User::find($userId);
            if ($user) {
                $user->notify(new SafetyTaskAssignedNotification($record));
                event(new SafetyTaskAssignedEvent($record));
            }
        }

        return $records;
    }

    /**
     * تحديث بيانات السجل الأساسية (بدون المخالفات).
     */
    public function update(string $id, array $data): SafetyRecord
    {
        $record = $this->repository->findOneOrFail($id);

        if ($record->status === 'completed') {
            throw new \Exception('لا يمكن تعديل مهمة مكتملة.');
        }

        $record->update(Arr::only($data, [
            'order_type', 'date', 'time', 'required_score', 'earned_score',
            'percentage', 'consultant_engineer', 'consultant', 'contractor_id',
        ]));

        return $this->show($record->id);
    }

    /**
     * تقييم المخالفات (خاص بالموظف).
     */
    public function evaluateViolations(string $id, array $violations): SafetyRecord
    {
        $record = $this->repository->findOneOrFail($id);

        if ($record->status === 'completed') {
            throw new \Exception('لا يمكن تقييم مهمة مكتملة.');
        }

        $this->syncViolations($record, $violations);
        $record->update(['status' => 'completed']);

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
                'id'          => $violation->id,
                'code'        => $violation->code,
                'description' => $violation->description,
                'category'    => $violation->category,
                'is_attached' => $isAttached,
                'weight'      => $pivot?->weight,
                'status'      => $pivot?->status,
            ];
        });
    }

    private function syncViolations(SafetyRecord $record, array $violations): void
    {
        $syncData = [];
        foreach ($violations as $v) {
            if (isset($v['violation_id'])) {
                $syncData[$v['violation_id']] = [
                    'weight' => $v['weight'] ?? null,
                    'status' => $v['status'] ?? null,
                ];
            }
        }
        $record->violations()->sync($syncData);
    }
}
