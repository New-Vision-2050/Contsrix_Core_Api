<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use Modules\Project\ProjectManagement\Models\AttachmentRequest;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\Process\Models\ProcessStep;
use Modules\Project\ProjectManagement\Models\AttachmentRequestHistory;
use Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;

class AttachmentRequestPresenter extends AbstractPresenter
{
    /**
     * @var array<string, array{id: string, name: mixed, email: ?string}|null>
     */
    private array $historyUserCache = [];

    public function __construct(private AttachmentRequest $request)
    {
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->request->id,
            'serial_number' => $this->request->serial_number,
            'name' => $this->request->name,
            'date' => $this->request->date?->toDateString(),
            'project_id' => $this->request->project_id,
            'procedure_setting_id' => $this->request->procedure_setting_id,
            'status' => $this->request->status,
            'type' => $this->request->sender_company_id === tenant('id') ? 'outgoing' : 'incoming',
            'notes' => $this->request->notes,
            'created_at' => $this->request->created_at?->toISOString(),
            'responded_at' => $this->request->responded_at?->toISOString(),
            'can_take_action' => $this->canTakeAction(),
        ];

            $data['project'] = $this->request->project ? [
                'id' => $this->request->project->id,
                'name' => $this->request->project->name,
                'serial_number' => $this->request->project->serial_number,
            ] : null;

            $projectProcedure = $this->request->projectProcedureSetting;

            $data['procedure_setting'] = $this->request->procedureSetting ? [
                'id' => $this->request->procedureSetting->id,
                'name' => $this->request->procedureSetting->name,
                'type' => $this->request->procedureSetting->type,
                'execute_type' => $this->request->procedureSetting->execute_type,
                'is_active' => (bool) $this->request->procedureSetting->is_active,
                'project_procedure_setting_id' => $projectProcedure?->id,
                'attachment_type' => $this->folderData($projectProcedure?->attachmentType),
                'attachment_sub_type' => $this->folderData($projectProcedure?->attachmentSubType),
                'attachment_sub_sub_type' => $this->folderData($projectProcedure?->attachmentSubSubType),
            ] : null;

            $data['sender_company'] = $this->request->senderCompany ? [
                'id' => $this->request->senderCompany->id,
                'name' => $this->request->senderCompany->name,
                'serial_number' => $this->request->senderCompany->serial_number,
            ] : null;

            $data['sender_company_name'] = $this->request->senderCompany
                ? $this->request->senderCompany->name
                : null;

            $data['created_by'] = $this->request->createdByUser ? [
                'id' => $this->request->createdByUser->id,
                'name' => $this->request->createdByUser->name,
                'email' => $this->request->createdByUser->email,
            ] : null;

            $data['responded_by'] = $this->request->respondedByUser ? [
                'id' => $this->request->respondedByUser->id,
                'name' => $this->request->respondedByUser->name,
                'email' => $this->request->respondedByUser->email,
            ] : null;

            // Include items
            if ($this->request->relationLoaded('items')) {
                $data['items'] = $this->request->items->map(function ($item) {
                    return (new AttachmentRequestItemPresenter($item))->getData();
                })->toArray();

                // Add attachments preview (simplified for quick view)
                $data['attachments_preview'] = $this->request->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'file_name' => $item->file_name,
                        'file_url' => $item->getFirstMediaUrl('attachments') ?: null,
                        'file_size' => $item->file_size,
                        'file_size_formatted' => $this->formatFileSize($item->file_size),
                        'file_type' => $item->file_type,
                        'status' => $item->status,
                    ];
                })->toArray();

                // Add statistics
                $totalItems = $this->request->items->count();
                $approvedItems = $this->request->items->where('status', 'approved')->count();
                $declinedItems = $this->request->items->where('status', 'declined')->count();
                $pendingItems = $this->request->items->where('status', 'pending')->count();
                $updateRequestedItems = $this->request->items->where('status', 'update_requested')->count();

                $data['statistics'] = [
                    'total_items' => $totalItems,
                    'approved_items' => $approvedItems,
                    'declined_items' => $declinedItems,
                    'pending_items' => $pendingItems,
                    'update_requested_items' => $updateRequestedItems,
                ];
            }

            // Add request history from database
            $historyEntries = $this->request->history;
            $this->preloadPendingHistoryUsers($historyEntries);

            $data['history'] = $historyEntries->map(function ($historyEntry) {
                return [
                    'id' => $historyEntry->id,
                    'action' => $historyEntry->action,
                    'description' => $historyEntry->description,
                    'user' => $this->historyUsers($historyEntry),
                    'timestamp' => $historyEntry->created_at?->toISOString(),
                    'metadata' => $historyEntry->metadata,
                ];
            })->toArray();

            $data['process'] = null;
            $data['process_steps'] = [];
            $data['workflow'] = null;
            if ($this->request->relationLoaded('attachmentRequestProcess')) {
                $process = $this->request->attachmentRequestProcess;
                if ($process !== null) {
                    $steps = $process->relationLoaded('steps') ? $process->steps : collect();
                    $processPayload = [
                        'id' => $process->id,
                        'status' => $process->status->value,
                        'execute_type' => $process->execute_type,
                        'type' => $process->processable_type,
                        'attachment_request_id' => $process->processable_id,
                        'created_at' => $process->created_at?->toIso8601String(),
                        'updated_at' => $process->updated_at?->toIso8601String(),
                    ];

                    $stepsPayload = $steps->map(static function (ProcessStep $step) {
                        return [
                            'id' => $step->id,
                            'process_id' => $step->process_id,
                            'step_id' => $step->step_id,
                            'template_step_order' => $step->template_step_order,
                            'assigned_user_id' => $step->assigned_user_id,
                            'authorized_user_ids' => $step->authorized_user_ids,
                            'escalation_management_hierarchy_id' => $step->escalation_management_hierarchy_id,
                            'status' => $step->status->value,
                            'action_by' => $step->action_by,
                            'acted_at' => $step->acted_at?->toIso8601String(),
                            'created_at' => $step->created_at?->toIso8601String(),
                            'updated_at' => $step->updated_at?->toIso8601String(),
                        ];
                    })->values()->all();

                    $data['process'] = $processPayload;
                    $data['process_steps'] = $stepsPayload;
                    $data['workflow'] = [
                        'process' => $processPayload,
                        'process_steps' => $stepsPayload,
                    ];
                }
            }


        return $data;
    }

    private function canTakeAction(): int
    {
        $userId = Auth::id();

        if ($userId === null) {
            return 0;
        }

        $userId = (string) $userId;

        $process = $this->request->relationLoaded('attachmentRequestProcess')
            ? $this->request->attachmentRequestProcess
            : null;

        if ($process === null || $process->status !== ProcessStatus::InProgress) {
            return 0;
        }

        $steps = $process->relationLoaded('steps') ? $process->steps : collect();

        foreach ($steps as $step) {
            if ($step->status !== ProcessStepStatus::Pending) {
                continue;
            }

            if ($step->assigned_user_id === $userId) {
                return 1;
            }

            $authorized = $step->authorized_user_ids ?? [];
            if (in_array($userId, $authorized, true)) {
                return 1;
            }
        }

        return 0;
    }

    /**
     * @return list<array{id: string, name: mixed, email: ?string}>
     */
    private function historyUsers(AttachmentRequestHistory $historyEntry): array
    {
        $metadata = $historyEntry->metadata ?? [];

        if (
            $historyEntry->action === 'workflow_step_pending'
            && ($metadata['status'] ?? null) === ProcessStepStatus::Pending->value
        ) {
            return $this->pendingWorkflowStepUsers((string) ($metadata['process_step_id'] ?? ''));
        }

        if ($historyEntry->user === null) {
            return [];
        }

        return [$this->presentUser($historyEntry->user)];
    }

    private function preloadPendingHistoryUsers($historyEntries): void
    {
        $userIds = $historyEntries
            ->flatMap(function (AttachmentRequestHistory $historyEntry): array {
                $metadata = $historyEntry->metadata ?? [];

                if (
                    $historyEntry->action !== 'workflow_step_pending'
                    || ($metadata['status'] ?? null) !== ProcessStepStatus::Pending->value
                ) {
                    return [];
                }

                $step = $this->findProcessStep((string) ($metadata['process_step_id'] ?? ''));

                return $step !== null ? $this->authorizedUserIdsForStep($step) : [];
            })
            ->filter()
            ->map(static fn ($userId): string => (string) $userId)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return;
        }

        User::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $userIds->all())
            ->get()
            ->each(function (User $user): void {
                $this->historyUserCache[(string) $user->id] = $this->presentUser($user);
            });

        foreach ($userIds as $userId) {
            $this->historyUserCache[$userId] ??= null;
        }
    }

    /**
     * @return list<array{id: string, name: mixed, email: ?string}>
     */
    private function pendingWorkflowStepUsers(string $processStepId): array
    {
        if ($processStepId === '') {
            return [];
        }

        $step = $this->findProcessStep($processStepId);
        if ($step === null) {
            return [];
        }

        return collect($this->authorizedUserIdsForStep($step))
            ->map(fn (string $userId): ?array => $this->resolveHistoryUser($userId))
            ->filter()
            ->values()
            ->all();
    }

    private function findProcessStep(string $processStepId): ?ProcessStep
    {
        $process = $this->request->relationLoaded('attachmentRequestProcess')
            ? $this->request->attachmentRequestProcess
            : null;

        if ($process !== null && $process->relationLoaded('steps')) {
            $step = $process->steps->firstWhere('id', $processStepId);
            if ($step instanceof ProcessStep) {
                return $step;
            }
        }

        return ProcessStep::query()->find($processStepId);
    }

    /**
     * @return list<string>
     */
    private function authorizedUserIdsForStep(ProcessStep $step): array
    {
        $userIds = $step->authorized_user_ids ?? [$step->assigned_user_id];

        return collect($userIds)
            ->filter()
            ->map(static fn ($userId): string => (string) $userId)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{id: string, name: mixed, email: ?string}|null
     */
    private function resolveHistoryUser(string $userId): ?array
    {
        if (! array_key_exists($userId, $this->historyUserCache)) {
            $user = User::query()
                ->withoutGlobalScopes()
                ->find($userId);

            $this->historyUserCache[$userId] = $user !== null
                ? $this->presentUser($user)
                : null;
        }

        return $this->historyUserCache[$userId];
    }

    /**
     * @return array{id: string, name: mixed, email: ?string}
     */
    private function presentUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    /**
     * Format file size to human readable format
     */
    private function formatFileSize(?int $bytes): string
    {
        if (!$bytes || $bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    private function folderData($folder): ?array
    {
        if (!$folder) {
            return null;
        }

        return [
            'id' => $folder->id,
            'name' => $folder->name,
            'parent_id' => $folder->parent_id,
            'project_id' => $folder->project_id,
        ];
    }
}
