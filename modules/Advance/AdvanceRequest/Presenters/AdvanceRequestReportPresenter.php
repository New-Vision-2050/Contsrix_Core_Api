<?php

declare(strict_types=1);

namespace Modules\Advance\AdvanceRequest\Presenters;

use Modules\Advance\AdvanceRequest\Models\AdvanceRequest;
use Modules\Process\Models\Process;
use Modules\Process\Models\ProcessStep;
use Modules\User\Models\User;

/**
 * Advance-request report shapes.
 *
 * - row(): table listing (admin/advance-requests/report)
 * - detail(): click-through — full request + every workflow step and the
 *   action taken on it (who approved/rejected, when)
 */
final class AdvanceRequestReportPresenter
{
    /**
     * @param array<string, string> $names user id => name, for authorized_user_ids
     */
    public function __construct(
        private readonly AdvanceRequest $advanceRequest,
        private readonly array $names = [],
    ) {}

    public static function rows(iterable $requests): array
    {
        $names = self::namesFor($requests);
        $rows = [];
        foreach ($requests as $request) {
            $rows[] = (new self($request, $names))->toRow();
        }

        return $rows;
    }

    public static function detail(AdvanceRequest $request): array
    {
        return (new self($request, self::namesFor([$request])))->toDetail();
    }

    public function toRow(): array
    {
        $request = $this->advanceRequest;
        $policy = $request->relationLoaded('advancePolicy') ? $request->advancePolicy : null;

        return [
            'id' => (string) $request->id,
            'employee' => $this->userSummary($request->relationLoaded('user') ? $request->user : null),
            'advance_policy' => $policy ? ['id' => (string) $policy->id, 'name' => $policy->name] : null,
            'amount' => (float) $request->amount,
            'installments' => $request->installments,
            'reason' => $request->reason,
            'status' => $request->status,
            'status_label' => $this->requestStatusLabel((string) $request->status),
            'workflow' => $this->workflowSummary(),
            'created_at' => $request->created_at?->toDateTimeString(),
        ];
    }

    public function toDetail(): array
    {
        $request = $this->advanceRequest;
        $row = $this->toRow();

        $row['notes'] = $request->notes;
        $row['attachments'] = $request->attachments ?? [];
        $row['rejection_reason'] = $request->rejection_reason;
        $row['requested_by'] = $this->userSummary($request->relationLoaded('requester') ? $request->requester : null);
        $row['approved_by'] = $this->userSummary($request->relationLoaded('approver') ? $request->approver : null);
        $row['approved_at'] = $request->approved_at?->toDateTimeString();
        $row['rejected_by'] = $this->userSummary($request->relationLoaded('rejector') ? $request->rejector : null);
        $row['rejected_at'] = $request->rejected_at?->toDateTimeString();

        $processes = [];
        $timeline = [];

        if ($request->relationLoaded('processes')) {
            foreach ($request->processes->sortBy('sort_order') as $process) {
                $steps = $this->presentSteps($process);
                $processes[] = [
                    'id' => (string) $process->id,
                    'form' => $process->relationLoaded('procedureSetting') ? $process->procedureSetting?->form : null,
                    'form_label' => $this->formLabel($process),
                    'status' => $process->status->value,
                    'status_label' => $this->processStatusLabel($process->status->value),
                    'steps' => $steps,
                ];

                foreach ($steps as $step) {
                    if ($step['action_by'] === null) {
                        continue;
                    }
                    $timeline[] = [
                        'process_id' => (string) $process->id,
                        'step_name' => $step['name'],
                        'step_order' => $step['order'],
                        'action' => $step['action'],
                        'action_label' => $step['action_label'],
                        'action_by' => $step['action_by'],
                        'acted_at' => $step['acted_at'],
                    ];
                }
            }
        }

        usort($timeline, fn ($a, $b) => strcmp((string) $a['acted_at'], (string) $b['acted_at']));

        $row['processes'] = $processes;
        $row['timeline'] = $timeline;
        $row['workflow']['auto_approved'] = $processes === [] && $request->status === AdvanceRequest::STATUS_APPROVED;

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function workflowSummary(): array
    {
        $processes = $this->advanceRequest->relationLoaded('processes')
            ? $this->advanceRequest->processes
            : collect();

        $active = $processes->first(fn (Process $process) => $process->status->value === 'in_progress')
            ?? $processes->sortByDesc('created_at')->first();

        if ($active === null) {
            return [
                'auto_approved' => $this->advanceRequest->status === AdvanceRequest::STATUS_APPROVED,
                'process_status' => null,
                'process_status_label' => null,
                'current_step' => null,
            ];
        }

        $pending = $active->relationLoaded('steps')
            ? $active->steps->first(fn (ProcessStep $step) => $step->status->value === 'pending')
            : null;

        return [
            'auto_approved' => false,
            'process_status' => $active->status->value,
            'process_status_label' => $this->processStatusLabel($active->status->value),
            'current_step' => $pending === null ? null : [
                'name' => $pending->relationLoaded('procedureSettingStep') ? $pending->procedureSettingStep?->name : null,
                'order' => $pending->template_step_order,
                'action_takers' => $this->authorizedUsers($pending),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function presentSteps(Process $process): array
    {
        if (! $process->relationLoaded('steps')) {
            return [];
        }

        $steps = [];
        foreach ($process->steps->sortBy('template_step_order') as $step) {
            $status = $step->status->value;
            $action = match ($status) {
                'approved' => 'approve',
                'rejected' => 'reject',
                default => null,
            };

            $steps[] = [
                'id' => (string) $step->id,
                'name' => $step->relationLoaded('procedureSettingStep') ? $step->procedureSettingStep?->name : null,
                'order' => $step->template_step_order,
                'is_approve' => $step->relationLoaded('procedureSettingStep') && $step->procedureSettingStep
                    ? (bool) $step->procedureSettingStep->is_approve
                    : null,
                'status' => $status,
                'status_label' => $this->stepStatusLabel($status),
                'action' => $action,
                'action_label' => $this->actionLabel($action),
                'assigned_to' => $this->userSummary($step->relationLoaded('assignedUser') ? $step->assignedUser : null),
                'authorized_users' => $this->authorizedUsers($step),
                'action_by' => $this->userSummary($step->relationLoaded('actionByUser') ? $step->actionByUser : null),
                'acted_at' => $step->acted_at?->toDateTimeString(),
            ];
        }

        return $steps;
    }

    /**
     * @return list<array{id: string, name: ?string}>
     */
    private function authorizedUsers(ProcessStep $step): array
    {
        $ids = $step->authorized_user_ids ?? [];
        if ($ids === [] && $step->assigned_user_id) {
            $ids = [(string) $step->assigned_user_id];
        }

        $users = [];
        foreach (array_values(array_filter($ids)) as $id) {
            $id = (string) $id;
            $users[] = [
                'id' => $id,
                'name' => $this->names[$id] ?? null,
            ];
        }

        return $users;
    }

    /**
     * @param iterable<AdvanceRequest> $requests
     * @return array<string, string>
     */
    private static function namesFor(iterable $requests): array
    {
        $ids = [];
        foreach ($requests as $request) {
            if (! $request->relationLoaded('processes')) {
                continue;
            }
            foreach ($request->processes as $process) {
                if (! $process->relationLoaded('steps')) {
                    continue;
                }
                foreach ($process->steps as $step) {
                    foreach ($step->authorized_user_ids ?? [] as $id) {
                        $ids[] = (string) $id;
                    }
                    if ($step->assigned_user_id) {
                        $ids[] = (string) $step->assigned_user_id;
                    }
                }
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (User $user) => [(string) $user->id => (string) $user->name])
            ->all();
    }

    private function userSummary(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'phone' => $user->phone ?? null,
        ];
    }

    private function formLabel(Process $process): string
    {
        $form = $process->relationLoaded('procedureSetting') ? $process->procedureSetting?->form : null;
        $locale = app()->getLocale();

        return match ($form) {
            'createAdvanceRequest' => $locale === 'ar' ? 'إنشاء طلب سلفة' : 'Create Advance Request',
            default => $locale === 'ar' ? 'اعتماد طلب السلفة' : 'Advance Request Approval',
        };
    }

    private function requestStatusLabel(string $status): string
    {
        $locale = app()->getLocale();
        $labels = [
            'pending' => ['ar' => 'قيد الانتظار', 'en' => 'Pending'],
            'approved' => ['ar' => 'مقبول', 'en' => 'Approved'],
            'rejected' => ['ar' => 'مرفوض', 'en' => 'Rejected'],
            'cancelled' => ['ar' => 'ملغي', 'en' => 'Cancelled'],
        ];

        return $labels[$status][$locale] ?? $labels[$status]['en'] ?? $status;
    }

    private function processStatusLabel(string $status): string
    {
        $locale = app()->getLocale();
        $labels = [
            'pending' => ['ar' => 'قيد الانتظار', 'en' => 'Pending'],
            'in_progress' => ['ar' => 'قيد التنفيذ', 'en' => 'In progress'],
            'completed' => ['ar' => 'مكتمل', 'en' => 'Completed'],
            'failed' => ['ar' => 'مرفوض', 'en' => 'Failed'],
        ];

        return $labels[$status][$locale] ?? $status;
    }

    private function stepStatusLabel(string $status): string
    {
        $locale = app()->getLocale();
        $labels = [
            'pending' => ['ar' => 'بانتظار الإجراء', 'en' => 'Waiting'],
            'approved' => ['ar' => 'تمت الموافقة', 'en' => 'Approved'],
            'rejected' => ['ar' => 'تم الرفض', 'en' => 'Rejected'],
        ];

        return $labels[$status][$locale] ?? $status;
    }

    private function actionLabel(?string $action): ?string
    {
        if ($action === null) {
            return null;
        }

        $locale = app()->getLocale();

        return match ($action) {
            'approve' => $locale === 'ar' ? 'وافق' : 'Approved',
            'reject' => $locale === 'ar' ? 'رفض' : 'Rejected',
            default => $action,
        };
    }
}
