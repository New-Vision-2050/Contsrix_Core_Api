<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Presenters;

use Modules\Attendance\Support\HoursFormatter;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\Process\Models\Process;
use Modules\User\Models\User;

/**
 * Presenter for the "Employee Tasks Detailed Report" dashboard.
 *
 * - row(): lightweight shape used by the table listing.
 * - detail(): full shape used by the "click for details" panel — includes
 *   every lifecycle request (create/start/end/approval/extension), each
 *   with its full approval chain (who accepted/rejected/still pending),
 *   locations, and timestamps, plus a chronological timeline and the
 *   final resolved status.
 */
final class EmployeeTaskReportPresenter
{
    public function __construct(private readonly EmployeeTaskRequest $task) {}

    public static function row(EmployeeTaskRequest $task): array
    {
        return (new self($task))->toRow();
    }

    public static function rows(iterable $tasks): array
    {
        $result = [];
        foreach ($tasks as $task) {
            $result[] = (new self($task))->toRow();
        }
        return $result;
    }

    public static function detail(EmployeeTaskRequest $task): array
    {
        return (new self($task))->toDetail();
    }

    public function toRow(): array
    {
        $task   = $this->task;
        $locale = app()->getLocale();

        return [
            'id'              => $task->id,
            'serial_number'   => $task->serial_number,
            'title'           => $task->title,
            'employee'        => $this->userSummary($task->relationLoaded('user') ? $task->user : null),
            'task_type'       => $task->relationLoaded('employeeTaskType') && $task->employeeTaskType
                ? ['id' => $task->employeeTaskType->id, 'name' => $task->employeeTaskType->name]
                : null,
            'task_date'       => $task->task_date?->format('Y-m-d'),
            'time_from'       => $this->formatInTimezone($task->time_from),
            'time_to'         => $this->formatInTimezone($task->time_to),
            'duration_hours'  => HoursFormatter::fromDecimalString($task->duration_hours),
            'total_task_hours'=> HoursFormatter::fromDecimalString($task->total_task_hours),
            'status'          => $task->status,
            'status_label'    => EmployeeTaskStatus::from($task->status)->label($locale),
            'final_status'       => $task->status,
            'final_status_label' => EmployeeTaskStatus::from($task->status)->label($locale),
            'task_location'   => [
                'latitude'      => (float) $task->task_latitude,
                'longitude'     => (float) $task->task_longitude,
                'radius_meters' => $task->radius_meters,
            ],
            'created_at'      => $this->formatInTimezone($task->created_at),
        ];
    }

    public function toDetail(): array
    {
        $task = $this->task;
        $row  = $this->toRow();

        $row['description']       = $task->description;
        $row['notes']             = $task->notes;
        $row['rejection_reason']  = $task->rejection_reason;
        $row['cancellation_reason'] = $task->cancellation_reason;

        $row['locations'] = [
            'task_location'  => [
                'latitude'      => (float) $task->task_latitude,
                'longitude'     => (float) $task->task_longitude,
                'radius_meters' => $task->radius_meters,
            ],
            'start_location' => $task->start_location,
            'end_location'   => $task->end_location,
        ];

        $row['approved_by']  = $this->userSummary($task->relationLoaded('approvedByUser') ? $task->approvedByUser : null);
        $row['approved_at']  = $this->formatInTimezone($task->approved_at);
        $row['rejected_by']  = $this->userSummary($task->relationLoaded('rejectedByUser') ? $task->rejectedByUser : null);
        $row['rejected_at']  = $this->formatInTimezone($task->rejected_at);
        $row['cancelled_by'] = $this->userSummary($task->relationLoaded('cancelledByUser') ? $task->cancelledByUser : null);
        $row['cancelled_at'] = $this->formatInTimezone($task->cancelled_at);

        $row['work_sessions'] = $task->relationLoaded('sessions')
            ? EmployeeTaskSessionPresenter::collection($task->sessions)
            : [];

        // One entry per lifecycle procedure instance: create, start(s), end(s),
        // completion-approval(s), extension(s) — each with its full step chain.
        $locale = app()->getLocale();
        $processes = [];

        // Process IDs already surfaced via start/end request entries — these must
        // not be misclassified as the create-task workflow.
        $linkedProcessIds = collect()
            ->merge($task->relationLoaded('startRequests') ? $task->startRequests->pluck('process_id') : [])
            ->merge($task->relationLoaded('endRequests') ? $task->endRequests->pluck('process_id') : [])
            ->filter()
            ->all();

        $createProcess = $task->relationLoaded('processes')
            ? $task->processes->first(fn (Process $p) =>
                ! in_array($p->id, $linkedProcessIds, true)
                && ($p->procedureSetting === null || in_array($p->procedureSetting?->form, ['createTask', 'createProjectNotificationTask', null], true)))
            : null;

        $processes[] = [
            'type'          => 'create',
            'type_label'    => $this->typeLabel('create', $locale),
            'status'        => $createProcess
                ? $this->processStatusLabel($createProcess)
                : ($task->approved_at !== null ? 'approved' : ($task->status === EmployeeTaskStatus::Rejected->value ? 'rejected' : 'pending')),
            'requested_by'  => $this->userSummary($task->relationLoaded('user') ? $task->user : null),
            'requested_at'  => $this->formatInTimezone($task->created_at),
            'reviewed_by'   => $this->userSummary($task->relationLoaded('approvedByUser') ? $task->approvedByUser : ($task->relationLoaded('rejectedByUser') ? $task->rejectedByUser : null)),
            'reviewed_at'   => $this->formatInTimezone($task->approved_at ?? $task->rejected_at),
            'notes'         => null,
            'location'      => [
                'latitude'  => (float) $task->task_latitude,
                'longitude' => (float) $task->task_longitude,
            ],
            'steps'         => $createProcess ? $this->presentProcessSteps($createProcess) : [],
        ];

        if ($task->relationLoaded('startRequests')) {
            foreach ($task->startRequests as $startRequest) {
                $processes[] = [
                    'type'         => 'start',
                    'type_label'   => $this->typeLabel('start', $locale),
                    'status'       => $startRequest->status,
                    'status_label' => $this->requestStatusLabel($startRequest->status, $locale),
                    'requested_by' => $this->userSummary($startRequest->relationLoaded('requestedByUser') ? $startRequest->requestedByUser : null),
                    'requested_at' => $this->formatInTimezone($startRequest->created_at),
                    'reviewed_by'  => $this->userSummary($startRequest->relationLoaded('reviewedByUser') ? $startRequest->reviewedByUser : null),
                    'reviewed_at'  => $this->formatInTimezone($startRequest->reviewed_at),
                    'notes'        => $startRequest->notes,
                    'review_notes' => $startRequest->review_notes,
                    'location'     => ($startRequest->latitude !== null && $startRequest->longitude !== null)
                        ? ['latitude' => (float) $startRequest->latitude, 'longitude' => (float) $startRequest->longitude]
                        : null,
                    'steps'        => $startRequest->relationLoaded('process') && $startRequest->process
                        ? $this->presentProcessSteps($startRequest->process)
                        : [],
                ];
            }
        }

        if ($task->relationLoaded('endRequests')) {
            foreach ($task->endRequests as $endRequest) {
                $processes[] = [
                    'type'         => 'end',
                    'type_label'   => $this->typeLabel('end', $locale),
                    'status'       => $endRequest->status,
                    'status_label' => $this->requestStatusLabel($endRequest->status, $locale),
                    'requested_by' => $this->userSummary($endRequest->relationLoaded('requestedByUser') ? $endRequest->requestedByUser : null),
                    'requested_at' => $this->formatInTimezone($endRequest->created_at),
                    'reviewed_by'  => $this->userSummary($endRequest->relationLoaded('reviewedByUser') ? $endRequest->reviewedByUser : null),
                    'reviewed_at'  => $this->formatInTimezone($endRequest->reviewed_at),
                    'notes'        => $endRequest->notes,
                    'review_notes' => $endRequest->review_notes,
                    'location'     => ($endRequest->latitude !== null && $endRequest->longitude !== null)
                        ? ['latitude' => (float) $endRequest->latitude, 'longitude' => (float) $endRequest->longitude]
                        : null,
                    'steps'        => $endRequest->relationLoaded('process') && $endRequest->process
                        ? $this->presentProcessSteps($endRequest->process)
                        : [],
                ];
            }
        }

        if ($task->relationLoaded('approvalRequests')) {
            foreach ($task->approvalRequests as $approvalRequest) {
                $processes[] = [
                    'type'         => 'approval',
                    'type_label'   => $this->typeLabel('approval', $locale),
                    'status'       => $approvalRequest->status,
                    'status_label' => $this->requestStatusLabel($approvalRequest->status, $locale),
                    'requested_by' => $this->userSummary($approvalRequest->relationLoaded('requestedByUser') ? $approvalRequest->requestedByUser : null),
                    'requested_at' => $this->formatInTimezone($approvalRequest->created_at),
                    'reviewed_by'  => $this->userSummary($approvalRequest->relationLoaded('reviewedByUser') ? $approvalRequest->reviewedByUser : null),
                    'reviewed_at'  => $this->formatInTimezone($approvalRequest->reviewed_at),
                    'notes'        => $approvalRequest->notes,
                    'review_notes' => $approvalRequest->review_notes,
                    'location'     => null,
                    'steps'        => [],
                ];
            }
        }

        if ($task->relationLoaded('extensionRequests')) {
            foreach ($task->extensionRequests as $extensionRequest) {
                $processes[] = [
                    'type'            => 'extension',
                    'type_label'      => $this->typeLabel('extension', $locale),
                    'status'          => $extensionRequest->status,
                    'status_label'    => $this->requestStatusLabel($extensionRequest->status, $locale),
                    'requested_by'    => $this->userSummary($extensionRequest->relationLoaded('requestedByUser') ? $extensionRequest->requestedByUser : null),
                    'requested_at'    => $this->formatInTimezone($extensionRequest->created_at),
                    'reviewed_by'     => $this->userSummary($extensionRequest->relationLoaded('reviewedByUser') ? $extensionRequest->reviewedByUser : null),
                    'reviewed_at'     => $this->formatInTimezone($extensionRequest->reviewed_at),
                    'notes'           => $extensionRequest->reason,
                    'review_notes'    => $extensionRequest->review_notes,
                    'additional_hours'=> HoursFormatter::fromDecimalString($extensionRequest->additional_hours),
                    'location'        => null,
                    'steps'           => [],
                ];
            }
        }

        // Sort chronologically by requested_at so the frontend can render a timeline directly.
        usort($processes, fn ($a, $b) => strcmp((string) $a['requested_at'], (string) $b['requested_at']));

        $row['processes'] = $processes;

        return $row;
    }

    private function presentProcessSteps(Process $process): array
    {
        if (! $process->relationLoaded('steps')) {
            return [];
        }

        $steps = [];
        foreach ($process->steps as $step) {
            $steps[] = [
                'id'             => $step->id,
                'name'           => $step->relationLoaded('procedureSettingStep') && $step->procedureSettingStep
                    ? $step->procedureSettingStep->name
                    : null,
                'order'          => $step->template_step_order,
                'is_approve'     => $step->relationLoaded('procedureSettingStep') && $step->procedureSettingStep
                    ? (bool) $step->procedureSettingStep->is_approve
                    : null,
                'status'         => $step->status->value,
                'assigned_to'    => $this->userSummary($step->relationLoaded('assignedUser') ? $step->assignedUser : null),
                'action_by'      => $this->userSummary($step->relationLoaded('actionByUser') ? $step->actionByUser : null),
                'acted_at'       => $this->formatInTimezone($step->acted_at),
            ];
        }

        return $steps;
    }

    private function userSummary(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'phone' => $user->phone ?? null,
        ];
    }

    private function typeLabel(string $type, string $locale): string
    {
        $labels = [
            'create'    => ['ar' => 'إنشاء المهمة', 'en' => 'Create Task'],
            'start'     => ['ar' => 'بدء المهمة',   'en' => 'Start Task'],
            'end'       => ['ar' => 'إنهاء المهمة',  'en' => 'End Task'],
            'approval'  => ['ar' => 'اعتماد الإنجاز', 'en' => 'Completion Approval'],
            'extension' => ['ar' => 'تمديد المهمة',  'en' => 'Extension'],
        ];

        return $labels[$type][$locale] ?? $labels[$type]['en'] ?? $type;
    }

    private function requestStatusLabel(string $status, string $locale): string
    {
        $labels = [
            'pending'  => ['ar' => 'قيد الانتظار', 'en' => 'Pending'],
            'approved' => ['ar' => 'معتمدة',       'en' => 'Approved'],
            'rejected' => ['ar' => 'مرفوضة',       'en' => 'Rejected'],
        ];

        return $labels[$status][$locale] ?? $labels[$status]['en'] ?? $status;
    }

    private function formatInTimezone(?\Carbon\Carbon $date): ?string
    {
        if (! $date) {
            return null;
        }

        $timezone = $this->task->timezone ?? getTimeZoneBranchByRequest();

        return $date->setTimezone($timezone)->format('Y-m-d H:i:s');
    }
}
