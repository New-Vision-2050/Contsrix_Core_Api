<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Carbon\Carbon;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;

class SafetySearchPresenter extends AbstractPresenter
{
    public function __construct(
        private readonly string $type,
        private readonly ProjectNotification|ProjectOrderPermit $model,
    ) {}

    protected function present(bool $isListing = false): array
    {
        return [
            'type' => $this->type,
            'item' => $this->type === 'notification'
                ? $this->presentNotification($this->model)
                : $this->presentOrderPermit($this->model),
        ];
    }

    private function presentNotification(ProjectNotification $notification): array
    {
        $assignmentDate = $notification->task_date?->toDateString();
        $assignedUser = $notification->assigned_users->first();

        return [
            'id' => $notification->id,
            'permit_number' => $notification->notification_number,
            'contractor' => $notification->contractor
                ? [
                    'id' => $notification->contractor->id,
                    'name' => $notification->contractor->name,
                ]
                : null,
            'department' => $notification->work_type,
            'type' => $notification->notification_type,
            'assigned_engineer' => $assignedUser?->name,
            // 'assigned_users' => $notification->assigned_users
            //     ->map(fn ($user) => [
            //         'id' => $user->id,
            //         'name' => $user->name,
            //     ])
            //     ->values()
            //     ->all(),
            'management' => $notification->work_description,
            'project_id' => $notification->project_id,
            'assignment_date' => $assignmentDate,
            'days_since_assignment' => $this->daysSince($assignmentDate),
            'price' => null,
            'payment_status' => null,
            'permit_status' => $notification->status,
            'status' => $notification->status,
            // 'task_time' => $notification->task_time?->format('H:i'),
            // 'severity' => $notification->severity,
        ];
    }

    private function presentOrderPermit(ProjectOrderPermit $permit): array
    {
        $assignmentDate = $permit->assigned_date?->toDateString();
        $permitStatus = $this->orderPermitStatus($permit);

        return [
            'id' => $permit->id,
            'permit_number' => $permit->name,
            'contractor' => $permit->contractor
                ? [
                    'id' => $permit->contractor->id,
                    'name' => $permit->contractor->name,
                ]
                : null,
            'department' => $permit->executing_entity,
            'type' => $permit->type,
            'assigned_engineer' => $permit->employee?->name,
            'management' => $permit->office,
            'project_id' => $permit->project_id,
            'assignment_date' => $assignmentDate,
            'days_since_assignment' => $this->daysSince($assignmentDate),
            'price' => $permit->price,
            'payment_status' => $permit->contractor_work_order_status,
            'permit_status' => $permitStatus,
            'status' => $permitStatus,
            // 'order_permit' => $permit->orderPermit
            //     ? [
            //         'id' => $permit->orderPermit->id,
            //         'code' => $permit->orderPermit->code,
            //         'description' => $permit->orderPermit->description,
            //         'type' => $permit->orderPermit->type,
            //     ]
            //     : null,
            // 'employee_id' => $permit->employee_id,
        ];
    }

    private function daysSince(?string $date): ?int
    {
        if ($date === null || $date === '') {
            return null;
        }

        return (int) Carbon::parse($date)->startOfDay()->diffInDays(Carbon::today()->startOfDay(), false);
    }

    /**
     * Mirror ProjectOrderPermitPresenter permit-cycle status when available,
     * otherwise fall back to contractor work-order status.
     */
    private function orderPermitStatus(ProjectOrderPermit $permit): ?string
    {
        $departmentName = $permit->department?->name
            ?? $permit->orderPermit?->department?->name;

        if ($departmentName === 'مشاريع') {
            $phaseName = $permit->projectCompletionPhase?->name;
            $statusName = $permit->projectPhaseStatus?->name;
        } elseif ($departmentName === 'توصيلات') {
            $phaseName = $permit->connectionCompletionPhase?->name;
            $statusName = $permit->connectionPhaseStatus?->name;
        } else {
            $phaseName = null;
            $statusName = null;
        }

        if ($phaseName === 'التصاريح') {
            return $statusName ?? 'لم ينشآ';
        }

        return $permit->contractor_work_order_status ?? $statusName;
    }
}
