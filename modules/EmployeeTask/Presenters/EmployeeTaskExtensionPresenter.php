<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Presenters;

use Modules\Attendance\Support\HoursFormatter;
use Modules\EmployeeTask\Models\EmployeeTaskExtensionRequest;

final class EmployeeTaskExtensionPresenter
{
    public function __construct(private readonly EmployeeTaskExtensionRequest $extension) {}


    public function toArray(): array
    {
        $e = $this->extension;

        return [
            'id'                       => $e->id,
            'employee_task_request_id' => $e->employee_task_request_id,
            'additional_hours'         => HoursFormatter::fromDecimalString($e->additional_hours),
            'reason'                   => $e->reason,
            'status'                   => $e->status,
            'status_label'             => $this->statusLabel($e->status),

            'requested_by'             => $e->relationLoaded('requestedByUser') && $e->requestedByUser
                ? [
                    'id'    => $e->requestedByUser->id,
                    'name'  => $e->requestedByUser->name,
                    'email' => $e->requestedByUser->email,
                ]
                : null,
            'reviewed_by'              => $e->relationLoaded('reviewedByUser') && $e->reviewedByUser
                ? [
                    'id'    => $e->reviewedByUser->id,
                    'name'  => $e->reviewedByUser->name,
                    'email' => $e->reviewedByUser->email,
                ]
                : null,
            'review_notes'             => $e->review_notes,
            'task'                     => $e->relationLoaded('task') && $e->task
                ? [
                    'id'    => $e->task->id,
                    'title' => $e->task->title,
                    'user'  => $e->task->relationLoaded('user') && $e->task->user ? ['id' => $e->task->user->id, 'name' => $e->task->user->name, 'email' => $e->task->user->email] : null,
                ]
                : null,
            'reviewed_at'              => $e->reviewed_at?->format('Y-m-d H:i:s'),
            'created_at'               => $e->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get human-readable status label.
     */
    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending'  => 'قيد الانتظار',
            'approved' => 'معتمدة',
            'rejected' => 'مرفوضة',
            default    => $status,
        };
    }

    /**
     * Create collection of presenters.
     */
    public static function collection(iterable $extensions): array
    {
        $result = [];
        foreach ($extensions as $ext) {
            $result[] = (new self($ext))->toArray();
        }
        return $result;
    }

    /**
     * Present single extension.
     */
    public static function single(EmployeeTaskExtensionRequest $extension): array
    {
        return (new self($extension))->toArray();
    }
}
