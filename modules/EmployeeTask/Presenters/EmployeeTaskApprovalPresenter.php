<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Presenters;

use Modules\Attendance\Support\HoursFormatter;
use Modules\EmployeeTask\Models\EmployeeTaskApprovalRequest;
use Modules\Shared\Media\Presenters\MediaPresenter;

final class EmployeeTaskApprovalPresenter
{
    public function __construct(private readonly EmployeeTaskApprovalRequest $approval) {}

    public function toArray(): array
    {
        $a = $this->approval;

        return [
            'id'                       => $a->id,
            'employee_task_request_id' => $a->employee_task_request_id,
            'notes'                    => $a->notes,
            'attachment_path'          => $a->attachment_path,
            'status'                   => $a->status,
            'status_label'             => $this->statusLabel($a->status),

            'requested_by' => $a->relationLoaded('requestedByUser') && $a->requestedByUser
                ? ['id' => $a->requestedByUser->id, 'name' => $a->requestedByUser->name, 'email' => $a->requestedByUser->email ?? null]
                : null,

            'reviewed_by' => $a->relationLoaded('reviewedByUser') && $a->reviewedByUser
                ? ['id' => $a->reviewedByUser->id, 'name' => $a->reviewedByUser->name, 'email' => $a->reviewedByUser->email ?? null]
                : null,

            'review_notes' => $a->review_notes,

            'task' => $a->relationLoaded('task') && $a->task
                ? [
                    'id'            => $a->task->id,
                    'title'         => $a->task->title,
                    'serial_number' => $a->task->serial_number,
                    'time_from'     => $a->task->time_from?->format('Y-m-d H:i:s'),
                    'time_to'       => $a->task->time_to?->format('Y-m-d H:i:s'),
                    'total_task_hours' => $a->task->total_task_hours
                        ? HoursFormatter::fromDecimalString($a->task->total_task_hours)
                        : null,
                    'user' => $a->task->relationLoaded('user') && $a->task->user
                        ? ['id' => $a->task->user->id, 'name' => $a->task->user->name]
                        : null,
                ]
                : null,

            'current_step' => $this->presentStep($a),
            'attachments'  => $a->relationLoaded('media')
                ? MediaPresenter::collection($a->getMedia('attachments'))
                : [],
            'reviewed_at'  => $a->reviewed_at?->format('Y-m-d H:i:s'),
            'created_at'   => $a->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public static function single(EmployeeTaskApprovalRequest $approval): array
    {
        return (new self($approval))->toArray();
    }

    public static function collection(iterable $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = (new self($item))->toArray();
        }
        return $result;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending'  => 'قيد الانتظار',
            'approved' => 'معتمدة',
            'rejected' => 'مرفوضة',
            default    => $status,
        };
    }

    private function presentStep(EmployeeTaskApprovalRequest $approval): ?array
    {
        if (!$approval->relationLoaded('currentProcedureStep') || !$approval->currentProcedureStep) {
            return null;
        }

        $step         = $approval->currentProcedureStep;
        $actionTakers = [];

        if ($step->relationLoaded('actionTakers') && $step->actionTakers->isNotEmpty()) {
            foreach ($step->actionTakers as $at) {
                $actionTakers[] = [
                    'user_id' => $at->user_id,
                    'name'    => $at->relationLoaded('user') && $at->user ? $at->user->name : null,
                ];
            }
        } elseif (
            $approval->relationLoaded('task')
            && $approval->task
            && $approval->task->approval_responsible_id
        ) {
            $actionTakers[] = [
                'user_id' => $approval->task->approval_responsible_id,
                'name'    => null,
            ];
        }

        return [
            'id'            => $step->id,
            'name'          => $step->name,
            'step_order'    => $step->step_order,
            'is_approve'    => (bool) $step->is_approve,
            'action_takers' => $actionTakers,
        ];
    }
}
