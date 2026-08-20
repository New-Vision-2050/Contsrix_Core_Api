<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PROCESSABLE_TYPE = 'attachment_request';

    public function up(): void
    {
        if (! $this->hasRequiredSchema()) {
            return;
        }

        DB::table('processes')
            ->where('processable_type', self::PROCESSABLE_TYPE)
            ->where('execute_type', 'sequence')
            ->where('status', 'in_progress')
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('process_steps')
                    ->whereColumn('process_steps.process_id', 'processes.id')
                    ->where('process_steps.status', 'pending');
            })
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($processes): void {
                foreach ($processes as $process) {
                    DB::transaction(function () use ($process): void {
                        $this->repairProcess((string) $process->id);
                    });
                }
            });
    }

    public function down(): void
    {
        // Only redundant legacy history rows are removed. They cannot be restored
        // without fabricating their original lifecycle record, actor, or timestamp.
    }

    private function repairProcess(string $processId): void
    {
        $process = DB::table('processes')
            ->where('id', $processId)
            ->lockForUpdate()
            ->first();

        if (
            $process === null
            || $process->processable_type !== self::PROCESSABLE_TYPE
            || $process->execute_type !== 'sequence'
            || $process->status !== 'in_progress'
        ) {
            return;
        }

        $request = DB::table('attachment_requests')
            ->where('id', $process->processable_id)
            ->lockForUpdate()
            ->first();

        if ($request === null) {
            return;
        }

        $steps = DB::table('process_steps')
            ->where('process_id', $processId)
            ->orderBy('template_step_order')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $pendingSteps = $steps
            ->where('status', 'pending')
            ->values();

        // A sequential process has exactly one active step. If the persisted
        // process state is itself ambiguous, leave it untouched rather than infer
        // a workflow transition that is not evidenced by the data.
        if ($pendingSteps->count() !== 1) {
            return;
        }

        $pendingStep = $pendingSteps->first();
        $snapshot = $this->decodeJson($process->template_snapshot);
        if ($snapshot === []) {
            return;
        }

        $history = DB::table('attachment_request_history')
            ->where('attachment_request_id', $request->id)
            ->whereIn('action', ['attachment_approved', 'workflow_step_pending'])
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $attachmentApprovals = $history
            ->where('action', 'attachment_approved')
            ->values();

        if ($attachmentApprovals->isEmpty()) {
            return;
        }

        $stalePendingIds = [];
        $attachmentApprovalUpdates = [];

        foreach ($attachmentApprovals as $approval) {
            $approvedStep = $this->approvedStepForAttachmentApproval($approval, $steps, $processId);

            if (
                $approvedStep === null
                || ! $this->isApprovalForCompletedStep($approval, $approvedStep)
                || ! $this->isNextSequentialStep($approvedStep, $pendingStep, $snapshot)
            ) {
                continue;
            }

            $hasStalePendingHistory = false;
            foreach ($history->where('action', 'workflow_step_pending') as $pendingHistory) {
                if ($this->isStalePendingHistory($pendingHistory, $approvedStep, $processId)) {
                    $stalePendingIds[] = $pendingHistory->id;
                    $hasStalePendingHistory = true;
                }
            }

            if ($hasStalePendingHistory) {
                $attachmentApprovalUpdates[(string) $approval->id] = $this->attachmentApprovalUpdate(
                    $approval,
                    $process,
                    $approvedStep
                );
            }
        }

        $stalePendingIds = array_values(array_unique($stalePendingIds));

        if ($stalePendingIds === []) {
            return;
        }

        DB::table('attachment_request_history')
            ->whereIn('id', $stalePendingIds)
            ->delete();

        foreach ($attachmentApprovalUpdates as $historyId => $updates) {
            DB::table('attachment_request_history')
                ->where('id', $historyId)
                ->update($updates);
        }

        // An in-progress process with a real current pending step cannot represent
        // a fully approved attachment request. Do not alter other request states,
        // which can carry legitimate item-level decisions.
        DB::table('attachment_requests')
            ->where('id', $request->id)
            ->where('status', 'approved')
            ->update(['status' => 'pending']);
    }

    /**
     * @return array{metadata: string, sort_order: int}
     */
    private function attachmentApprovalUpdate(object $approval, object $process, object $step): array
    {
        $metadata = $this->decodeJson($approval->metadata);

        // The approval itself is real. Only restore its workflow context from the
        // authoritative persisted process step so presentation follows the actual
        // sequence without changing the approval's actor or created_at timestamp.
        $metadata['process_id'] = (string) $process->id;
        $metadata['process_sort_order'] = (int) ($process->sort_order ?? 0);
        $metadata['process_step_id'] = (string) $step->id;
        $metadata['step_id'] = (int) $step->step_id;
        $metadata['template_step_order'] = (int) $step->template_step_order;
        $metadata['status'] = 'approved';

        return [
            'metadata' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'sort_order' => $this->workflowSortOrder($process, $step),
        ];
    }

    private function workflowSortOrder(object $process, object $step): int
    {
        return 100000
            + ((int) ($process->sort_order ?? 0) * 1000)
            + (int) $step->template_step_order;
    }

    private function approvedStepForAttachmentApproval(object $approval, $steps, string $processId): ?object
    {
        $metadata = $this->decodeJson($approval->metadata);
        $processStepId = $this->stringValue($metadata['process_step_id'] ?? null);

        if ($processStepId !== null) {
            $step = $steps->firstWhere('id', $processStepId);

            return $step !== null && $step->status === 'approved' ? $step : null;
        }

        if ($this->stringValue($metadata['process_id'] ?? null) !== $processId) {
            return null;
        }

        $stepId = $this->stringValue($metadata['step_id'] ?? null);
        $templateStepOrder = $this->stringValue($metadata['template_step_order'] ?? null);

        if ($stepId === null || $templateStepOrder === null) {
            return null;
        }

        $matchingSteps = $steps
            ->filter(static fn ($step): bool =>
                (string) $step->step_id === $stepId
                && (string) $step->template_step_order === $templateStepOrder
                && $step->status === 'approved')
            ->values();

        return $matchingSteps->count() === 1 ? $matchingSteps->first() : null;
    }

    private function isApprovalForCompletedStep(object $approval, object $step): bool
    {
        $approvalUserId = $this->stringValue($approval->user_id);
        $stepActorId = $this->stringValue($step->action_by);

        return $step->status === 'approved'
            && $approvalUserId !== null
            && $approvalUserId === $stepActorId;
    }

    private function isNextSequentialStep(object $approvedStep, object $pendingStep, array $snapshot): bool
    {
        foreach ($snapshot as $index => $stepConfig) {
            if (! is_array($stepConfig) || ! $this->snapshotMatchesStep($stepConfig, $approvedStep)) {
                continue;
            }

            $nextStepConfig = $snapshot[$index + 1] ?? null;

            return is_array($nextStepConfig)
                && $this->snapshotMatchesStep($nextStepConfig, $pendingStep);
        }

        return false;
    }

    private function snapshotMatchesStep(array $snapshot, object $step): bool
    {
        $stepId = $this->stringValue($snapshot['step_id'] ?? null);
        $templateStepOrder = $this->stringValue($snapshot['template_step_order'] ?? null);

        return $stepId !== null
            && $templateStepOrder !== null
            && $stepId === (string) $step->step_id
            && $templateStepOrder === (string) $step->template_step_order;
    }

    private function isStalePendingHistory(object $history, object $approvedStep, string $processId): bool
    {
        $metadata = $this->decodeJson($history->metadata);

        if (($metadata['status'] ?? null) !== 'pending') {
            return false;
        }

        $processStepId = $this->stringValue($metadata['process_step_id'] ?? null);
        if ($processStepId !== null) {
            return $processStepId === (string) $approvedStep->id;
        }

        return $this->stringValue($metadata['process_id'] ?? null) === $processId
            && $this->stringValue($metadata['step_id'] ?? null) === (string) $approvedStep->step_id
            && $this->stringValue($metadata['template_step_order'] ?? null) === (string) $approvedStep->template_step_order;
    }

    /**
     * @return array<int|string, mixed>
     */
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

    private function stringValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private function hasRequiredSchema(): bool
    {
        return Schema::hasTable('attachment_requests')
            && Schema::hasTable('processes')
            && Schema::hasTable('process_steps')
            && Schema::hasTable('attachment_request_history')
            && Schema::hasColumn('attachment_requests', 'id')
            && Schema::hasColumn('attachment_requests', 'status')
            && Schema::hasColumn('processes', 'id')
            && Schema::hasColumn('processes', 'processable_id')
            && Schema::hasColumn('processes', 'processable_type')
            && Schema::hasColumn('processes', 'execute_type')
            && Schema::hasColumn('processes', 'status')
            && Schema::hasColumn('processes', 'template_snapshot')
            && Schema::hasColumn('process_steps', 'id')
            && Schema::hasColumn('process_steps', 'process_id')
            && Schema::hasColumn('process_steps', 'step_id')
            && Schema::hasColumn('process_steps', 'template_step_order')
            && Schema::hasColumn('process_steps', 'status')
            && Schema::hasColumn('process_steps', 'action_by')
            && Schema::hasColumn('attachment_request_history', 'id')
            && Schema::hasColumn('attachment_request_history', 'attachment_request_id')
            && Schema::hasColumn('attachment_request_history', 'action')
            && Schema::hasColumn('attachment_request_history', 'user_id')
            && Schema::hasColumn('attachment_request_history', 'metadata');
    }
};
