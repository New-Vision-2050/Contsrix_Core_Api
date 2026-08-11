<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PROCESSABLE_TYPE = 'attachment_request';

    private const WORKFLOW_HISTORY_ACTIONS = [
        'attachment_approved',
        'workflow_step_pending',
        'workflow_step_approved',
        'workflow_step_rejected',
    ];

    public function up(): void
    {
        if (! $this->hasRequiredSchema()) {
            return;
        }

        $this->backfillWorkflowHistoryMetadata();
        $this->removeDuplicateAttachmentApprovals();
        $this->repairPrematureApprovedRequests();
    }

    public function down(): void
    {
        // Historical deduplication/status repair is not safely reversible.
        // Rollback is intentionally a safe no-op.
    }

    private function hasRequiredSchema(): bool
    {
        return Schema::hasTable('attachment_requests')
            && Schema::hasTable('attachment_request_items')
            && Schema::hasTable('attachment_request_history')
            && Schema::hasTable('processes')
            && Schema::hasTable('process_steps')
            && Schema::hasColumn('attachment_request_history', 'metadata')
            && Schema::hasColumn('attachment_request_history', 'sort_order');
    }

    private function backfillWorkflowHistoryMetadata(): void
    {
        DB::table('attachment_request_history')
            ->whereIn('action', self::WORKFLOW_HISTORY_ACTIONS)
            ->orderBy('id')
            ->chunk(500, function ($historyRows): void {
                $context = $this->workflowContextForRows($historyRows);

                foreach ($historyRows as $historyRow) {
                    $metadata = $this->decodeJson($historyRow->metadata);
                    $workflowContext = $this->resolveWorkflowContext($historyRow, $metadata, $context);

                    if ($workflowContext !== null) {
                        $metadata = $this->mergeWorkflowMetadata($historyRow->action, $metadata, $workflowContext);
                    }

                    $sortOrder = $this->workflowSortOrder($metadata);
                    $updates = [];

                    if ($this->jsonChanged($historyRow->metadata, $metadata)) {
                        $updates['metadata'] = $this->encodeJson($metadata);
                    }

                    if ($sortOrder !== null && (int) ($historyRow->sort_order ?? -1) !== $sortOrder) {
                        $updates['sort_order'] = $sortOrder;
                    }

                    if ($updates !== []) {
                        DB::table('attachment_request_history')
                            ->where('id', $historyRow->id)
                            ->update($updates);
                    }
                }
            });
    }

    private function removeDuplicateAttachmentApprovals(): void
    {
        $lastRequestId = null;

        do {
            $query = DB::table('attachment_request_history')
                ->where('action', 'attachment_approved')
                ->select('attachment_request_id')
                ->distinct()
                ->orderBy('attachment_request_id')
                ->limit(200);

            if ($lastRequestId !== null) {
                $query->where('attachment_request_id', '>', $lastRequestId);
            }

            $requestIds = $query
                ->pluck('attachment_request_id')
                ->map(static fn ($requestId): string => (string) $requestId)
                ->all();

            foreach ($requestIds as $requestId) {
                $this->removeDuplicateAttachmentApprovalsForRequest($requestId);
            }

            $lastRequestId = $requestIds === [] ? $lastRequestId : end($requestIds);
        } while (count($requestIds) === 200);
    }

    private function removeDuplicateAttachmentApprovalsForRequest(string $requestId): void
    {
        $approvals = DB::table('attachment_request_history')
            ->where('attachment_request_id', $requestId)
            ->where('action', 'attachment_approved')
            ->get();

        $groups = [];
        foreach ($approvals as $approval) {
            $identity = $this->approvalIdentity($approval);

            if ($identity === null) {
                continue;
            }

            $groups[$identity][] = $approval;
        }

        foreach ($groups as $group) {
            if (count($group) < 2) {
                continue;
            }

            $canonical = $this->canonicalApproval($group);
            $duplicateIds = collect($group)
                ->pluck('id')
                ->reject(static fn ($id): bool => $id === $canonical->id)
                ->values()
                ->all();

            if ($duplicateIds !== []) {
                DB::table('attachment_request_history')
                    ->whereIn('id', $duplicateIds)
                    ->delete();
            }
        }
    }

    private function repairPrematureApprovedRequests(): void
    {
        do {
            $requestIds = DB::table('attachment_requests as requests')
                ->join('processes as processes', function ($join): void {
                    $join->on('processes.processable_id', '=', 'requests.id')
                        ->where('processes.processable_type', self::PROCESSABLE_TYPE);
                })
                ->where('requests.status', 'approved')
                ->whereIn('processes.status', ['pending', 'in_progress'])
                ->whereExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('process_steps')
                        ->whereColumn('process_steps.process_id', 'processes.id')
                        ->where('process_steps.status', 'pending');
                })
                ->select('requests.id')
                ->distinct()
                ->orderBy('requests.id')
                ->limit(500)
                ->pluck('id')
                ->all();

            if ($requestIds === []) {
                return;
            }

            DB::table('attachment_requests')
                ->whereIn('id', $requestIds)
                ->where('status', 'approved')
                ->update(['status' => 'pending']);
        } while (count($requestIds) === 500);
    }

    private function workflowContextForRows($historyRows): array
    {
        $requestIds = collect($historyRows)
            ->pluck('attachment_request_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($requestIds === []) {
            return [
                'process_by_id' => [],
                'processes_by_request' => [],
                'steps_by_id' => [],
                'steps_by_identity' => [],
                'steps_by_order' => [],
                'approved_steps_by_request_actor' => [],
            ];
        }

        $processes = DB::table('processes')
            ->where('processable_type', self::PROCESSABLE_TYPE)
            ->whereIn('processable_id', $requestIds)
            ->get();

        $processById = [];
        $processesByRequest = [];

        foreach ($processes as $process) {
            $processById[(string) $process->id] = $process;
            $processesByRequest[(string) $process->processable_id][] = $process;
        }

        $processIds = array_keys($processById);

        if ($processIds === []) {
            return [
                'process_by_id' => [],
                'processes_by_request' => $processesByRequest,
                'steps_by_id' => [],
                'steps_by_identity' => [],
                'steps_by_order' => [],
                'approved_steps_by_request_actor' => [],
            ];
        }

        $steps = DB::table('process_steps')
            ->whereIn('process_id', $processIds)
            ->get();

        $stepsById = [];
        $stepsByIdentity = [];
        $stepsByOrder = [];
        $approvedStepsByRequestActor = [];

        foreach ($steps as $step) {
            $process = $processById[(string) $step->process_id] ?? null;
            if ($process === null) {
                continue;
            }

            $stepsById[(string) $step->id] = $step;

            if ($step->step_id !== null && $step->template_step_order !== null) {
                $stepsByIdentity[$this->stepIdentityKey(
                    (string) $step->process_id,
                    (string) $step->step_id,
                    (string) $step->template_step_order
                )][] = $step;
            }

            if ($step->template_step_order !== null) {
                $stepsByOrder[(string) $step->process_id.'|'.(string) $step->template_step_order][] = $step;
            }

            $actionBy = $this->stringValue($step->action_by ?? null);
            if ($step->status === 'approved' && $actionBy !== null) {
                $approvedStepsByRequestActor[(string) $process->processable_id.'|'.$actionBy][] = $step;
            }
        }

        return [
            'process_by_id' => $processById,
            'processes_by_request' => $processesByRequest,
            'steps_by_id' => $stepsById,
            'steps_by_identity' => $stepsByIdentity,
            'steps_by_order' => $stepsByOrder,
            'approved_steps_by_request_actor' => $approvedStepsByRequestActor,
        ];
    }

    private function resolveWorkflowContext(object $historyRow, array $metadata, array $context): ?array
    {
        $process = null;
        $step = null;
        $processId = $this->stringValue($metadata['process_id'] ?? null);
        $processStepId = $this->stringValue($metadata['process_step_id'] ?? null);

        if ($processId !== null) {
            $process = $context['process_by_id'][$processId] ?? null;
        }

        if ($processStepId !== null) {
            $step = $context['steps_by_id'][$processStepId] ?? null;
            if ($step !== null) {
                $process = $context['process_by_id'][(string) $step->process_id] ?? $process;
            }
        }

        if ($step === null && $process !== null) {
            $step = $this->findStepForProcess($process, $metadata, $context);
        }

        if ($process === null || $step === null) {
            $requestProcesses = $context['processes_by_request'][(string) $historyRow->attachment_request_id] ?? [];

            if (count($requestProcesses) === 1) {
                $process = $process ?? $requestProcesses[0];
                $step = $step ?? $this->findStepForProcess($process, $metadata, $context);
            }
        }

        if ($step === null && $historyRow->action === 'attachment_approved') {
            $actorId = $this->stringValue($historyRow->user_id ?? null);
            $approvedSteps = $actorId === null
                ? []
                : ($context['approved_steps_by_request_actor'][(string) $historyRow->attachment_request_id.'|'.$actorId] ?? []);

            $approvedSteps = $this->filterStepsByMetadata($approvedSteps, $metadata);
            if (count($approvedSteps) === 1) {
                $step = $approvedSteps[0];
                $process = $context['process_by_id'][(string) $step->process_id] ?? $process;
            }
        }

        if ($process === null && $step === null) {
            return null;
        }

        return [
            'process' => $process,
            'step' => $step,
        ];
    }

    private function findStepForProcess(object $process, array $metadata, array $context): ?object
    {
        $stepId = $this->stringValue($metadata['step_id'] ?? null);
        $templateStepOrder = $this->stringValue($metadata['template_step_order'] ?? null);

        if ($stepId !== null && $templateStepOrder !== null) {
            $steps = $context['steps_by_identity'][$this->stepIdentityKey((string) $process->id, $stepId, $templateStepOrder)] ?? [];
            if (count($steps) === 1) {
                return $steps[0];
            }
        }

        if ($templateStepOrder !== null) {
            $steps = $context['steps_by_order'][(string) $process->id.'|'.$templateStepOrder] ?? [];
            if (count($steps) === 1) {
                return $steps[0];
            }
        }

        return null;
    }

    private function filterStepsByMetadata(array $steps, array $metadata): array
    {
        $stepId = $this->stringValue($metadata['step_id'] ?? null);
        $templateStepOrder = $this->stringValue($metadata['template_step_order'] ?? null);

        return array_values(array_filter($steps, function ($step) use ($stepId, $templateStepOrder): bool {
            if ($stepId !== null && (string) $step->step_id !== $stepId) {
                return false;
            }

            if ($templateStepOrder !== null && (string) $step->template_step_order !== $templateStepOrder) {
                return false;
            }

            return true;
        }));
    }

    private function mergeWorkflowMetadata(string $action, array $metadata, array $context): array
    {
        $process = $context['process'] ?? null;
        $step = $context['step'] ?? null;

        if ($process !== null) {
            $metadata['process_id'] = (string) $process->id;
            $metadata['process_sort_order'] = $process->sort_order === null ? null : (int) $process->sort_order;
        }

        if ($step !== null) {
            if ($this->shouldBackfillProcessStepId($action, $metadata, $step)) {
                $metadata['process_step_id'] = (string) $step->id;
            }

            $metadata['step_id'] = $step->step_id === null ? null : (int) $step->step_id;
            $metadata['template_step_order'] = $step->template_step_order === null ? null : (int) $step->template_step_order;
            $metadata['assigned_user_id'] = $this->stringValue($step->assigned_user_id ?? null);

            $authorizedUserIds = $this->authorizedUserIdsForStep($step);
            if ($authorizedUserIds !== []) {
                $metadata['authorized_user_ids'] = $authorizedUserIds;
            }
        }

        $metadata['status'] = match ($action) {
            'workflow_step_pending' => 'pending',
            'workflow_step_rejected' => 'rejected',
            'attachment_approved', 'workflow_step_approved' => 'approved',
            default => $metadata['status'] ?? null,
        };

        return $metadata;
    }

    private function shouldBackfillProcessStepId(string $action, array $metadata, object $step): bool
    {
        if ($action !== 'workflow_step_pending') {
            return true;
        }

        if ($this->stringValue($metadata['process_step_id'] ?? null) !== null) {
            return true;
        }

        return $step->status !== 'pending';
    }

    private function approvalIdentity(object $approval): ?string
    {
        $itemId = $this->historyItemId($approval);
        if ($itemId === null) {
            return null;
        }

        $metadata = $this->decodeJson($approval->metadata);
        $processId = $this->stringValue($metadata['process_id'] ?? null);
        $stepId = $this->stringValue($metadata['step_id'] ?? null);
        $templateStepOrder = $this->stringValue($metadata['template_step_order'] ?? null);

        if ($processId !== null && $stepId !== null && $templateStepOrder !== null) {
            return implode('|', ['workflow', $itemId, $processId, $stepId, $templateStepOrder]);
        }

        $userId = $this->stringValue($approval->user_id ?? null);
        if ($userId === null) {
            return null;
        }

        return implode('|', [
            'legacy',
            $itemId,
            $userId,
            $this->legacyApprovalFingerprint($metadata),
        ]);
    }

    private function canonicalApproval(array $approvals): object
    {
        usort($approvals, function ($left, $right): int {
            $leftScore = $this->approvalScore($left);
            $rightScore = $this->approvalScore($right);

            if ($leftScore !== $rightScore) {
                return $rightScore <=> $leftScore;
            }

            $leftSort = $left->sort_order === null ? PHP_INT_MAX : (int) $left->sort_order;
            $rightSort = $right->sort_order === null ? PHP_INT_MAX : (int) $right->sort_order;

            if ($leftSort !== $rightSort) {
                return $leftSort <=> $rightSort;
            }

            $leftCreated = (string) ($left->created_at ?? '');
            $rightCreated = (string) ($right->created_at ?? '');

            if ($leftCreated !== $rightCreated) {
                return $leftCreated <=> $rightCreated;
            }

            return (string) $left->id <=> (string) $right->id;
        });

        return $approvals[0];
    }

    private function approvalScore(object $approval): int
    {
        $metadata = $this->decodeJson($approval->metadata);
        $score = $approval->sort_order === null ? 0 : 10;

        foreach ([
            'process_id',
            'process_step_id',
            'process_sort_order',
            'step_id',
            'template_step_order',
            'file_name',
            'file_path',
            'file_size',
            'response_notes',
        ] as $key) {
            if (array_key_exists($key, $metadata) && $metadata[$key] !== null) {
                $score++;
            }
        }

        return $score;
    }

    private function legacyApprovalFingerprint(array $metadata): string
    {
        ksort($metadata);

        return hash('sha256', $this->encodeJson($metadata));
    }

    private function workflowSortOrder(array $metadata): ?int
    {
        if (
            ! array_key_exists('process_sort_order', $metadata)
            || ! array_key_exists('template_step_order', $metadata)
            || $metadata['process_sort_order'] === null
            || $metadata['template_step_order'] === null
        ) {
            return null;
        }

        return 100000
            + ((int) $metadata['process_sort_order'] * 1000)
            + (int) $metadata['template_step_order'];
    }

    private function stepIdentityKey(string $processId, string $stepId, string $templateStepOrder): string
    {
        return $processId.'|'.$stepId.'|'.$templateStepOrder;
    }

    private function historyItemId(object $historyRow): ?string
    {
        $columnItemId = $this->stringValue($historyRow->attachment_request_item_id ?? null);
        if ($columnItemId !== null) {
            return $columnItemId;
        }

        $metadata = $this->decodeJson($historyRow->metadata);

        return $this->stringValue($metadata['item_id'] ?? null);
    }

    private function authorizedUserIdsForStep(object $step): array
    {
        $authorizedUserIds = $this->decodeJson($step->authorized_user_ids ?? null);

        if ($authorizedUserIds === []) {
            $assignedUserId = $this->stringValue($step->assigned_user_id ?? null);

            return $assignedUserId === null ? [] : [$assignedUserId];
        }

        return collect($authorizedUserIds)
            ->filter()
            ->map(static fn ($userId): string => (string) $userId)
            ->unique()
            ->values()
            ->all();
    }

    private function jsonChanged(mixed $originalJson, array $metadata): bool
    {
        return $this->encodeJson($this->decodeJson($originalJson)) !== $this->encodeJson($metadata);
    }

    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function encodeJson(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function stringValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
};
