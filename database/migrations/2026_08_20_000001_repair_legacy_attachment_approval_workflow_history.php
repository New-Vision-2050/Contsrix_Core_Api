<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

        if ($this->repairAlreadyAdvancedProcess(
            $process,
            $request,
            $steps,
            $snapshot,
            $history,
            $attachmentApprovals
        )) {
            return;
        }

        $this->repairMissingSequentialAdvance(
            $process,
            $request,
            $steps,
            $snapshot,
            $history,
            $attachmentApprovals
        );
    }

    /**
     * Repair the original, milder legacy shape: the completed step and its next
     * pending step were both persisted, but the completed step's pending history
     * survived and the item approval lost its workflow context.
     */
    private function repairAlreadyAdvancedProcess(
        object $process,
        object $request,
        $steps,
        array $snapshot,
        $history,
        $attachmentApprovals
    ): bool {
        $processId = (string) $process->id;
        $pendingSteps = $steps
            ->where('status', 'pending')
            ->values();

        // A sequential process has exactly one active step. If the persisted
        // process state is itself ambiguous, leave it untouched rather than infer
        // a workflow transition that is not evidenced by the data.
        if ($pendingSteps->count() !== 1) {
            return false;
        }

        $pendingStep = $pendingSteps->first();

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
            return false;
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

        return true;
    }

    /**
     * Repair the production legacy shape where an attachment approval proves the
     * current pending step completed, but that completion and its next step were
     * never persisted. This intentionally requires corroboration from the item,
     * the current ProcessStep, and the immutable template snapshot. The snapshot
     * is authoritative for the next sequential step once completion is proven.
     */
    private function repairMissingSequentialAdvance(
        object $process,
        object $request,
        $steps,
        array $snapshot,
        $history,
        $attachmentApprovals
    ): void {
        $snapshotSteps = $this->validatedSnapshotSteps($snapshot);
        if ($snapshotSteps === null) {
            return;
        }

        $current = $this->missingAdvanceCurrentStep($steps, $snapshotSteps);
        if ($current === null) {
            return;
        }

        $items = DB::table('attachment_request_items')
            ->where('attachment_request_id', $request->id)
            ->lockForUpdate()
            ->get()
            ->keyBy(static fn ($item): string => (string) $item->id);

        $approvalCandidates = [];
        foreach ($attachmentApprovals as $approval) {
            $item = $this->approvedItemForHistory($approval, $items);

            if (
                $item !== null
                && $this->approvalProvesCurrentStepCompletion(
                    $approval,
                    $item,
                    $process,
                    $current['step'],
                    $current['snapshot']
                )
            ) {
                $approvalCandidates[] = [
                    'approval' => $approval,
                    'item' => $item,
                ];
            }
        }

        // More than one matching approval gives no safe way to choose which item
        // action advanced this single persisted ProcessStep.
        if (count($approvalCandidates) !== 1) {
            return;
        }

        $approval = $approvalCandidates[0]['approval'];
        $item = $approvalCandidates[0]['item'];
        $stalePendingIds = $history
            ->where('action', 'workflow_step_pending')
            ->filter(fn ($pendingHistory): bool => $this->isStalePendingHistory(
                $pendingHistory,
                $current['step'],
                (string) $process->id
            ))
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->unique()
            ->values()
            ->all();

        if ($stalePendingIds === []) {
            return;
        }

        $nextSnapshot = $snapshotSteps[$current['index'] + 1] ?? null;

        if ($nextSnapshot !== null) {
            // Skip when the snapshot references a procedure setting step that has
            // since been deleted. The foreign key on process_steps.step_id would
            // otherwise reject the insert, halting the entire migration.
            if (! $this->snapshotStepExists($nextSnapshot)) {
                return;
            }

            // Never create a second representation of the next snapshot step.
            // missingAdvanceCurrentStep() already establishes an exact persisted
            // prefix; this explicit check also protects against an unexpected
            // conflicting row in a changed or partially repaired process.
            if ($this->hasPersistedNextStepConflict($steps, $nextSnapshot)) {
                return;
            }

            $nextPendingHistory = $history
                ->where('action', 'workflow_step_pending')
                ->filter(fn ($pendingHistory): bool => $this->isRestorableNextPendingHistory(
                    $pendingHistory,
                    $process,
                    $nextSnapshot
                ))
                ->values();

            // A matching record is reused. More than one record, or another
            // incompatible lifecycle record for this exact snapshot step, is
            // ambiguous and is therefore intentionally left untouched.
            if (
                $nextPendingHistory->count() > 1
                || $this->hasConflictingNextPendingHistory($history, $process, $nextSnapshot)
            ) {
                return;
            }

            $this->markCurrentStepApproved(
                $current['step'],
                (string) $approval->user_id,
                (string) $item->responded_at
            );

            $nextStepId = (string) Str::uuid();
            $this->restorePendingStep(
                $nextStepId,
                $process,
                $nextSnapshot,
                $nextPendingHistory->isNotEmpty()
                    ? (string) $nextPendingHistory->first()->created_at
                    : (string) $item->responded_at
            );

            if ($nextPendingHistory->isNotEmpty()) {
                DB::table('attachment_request_history')
                    ->where('id', $nextPendingHistory->first()->id)
                    ->update($this->restoredPendingHistoryUpdate($process, $nextStepId, $nextSnapshot));
            } else {
                $this->createPendingHistoryFromSnapshot(
                    $request,
                    $process,
                    $nextStepId,
                    $nextSnapshot,
                    (string) $item->responded_at
                );
            }

            $this->deleteAndEnrichCompletionHistory(
                $stalePendingIds,
                $approval,
                $process,
                $current['step']
            );

            // The reconstructed Step 3 is now the active workflow step. A request
            // that had been marked approved by the old item-only flow must remain
            // pending while that step awaits action.
            DB::table('attachment_requests')
                ->where('id', $request->id)
                ->where('status', 'approved')
                ->update(['status' => 'pending']);

            return;
        }

        // For a final step, completion is safe only when every item is already
        // approved. We never fabricate an item decision during this migration.
        if (! $this->allItemsApproved($items) || ! in_array($request->status, ['pending', 'approved'], true)) {
            return;
        }

        $this->markCurrentStepApproved(
            $current['step'],
            (string) $approval->user_id,
            (string) $item->responded_at
        );
        $this->deleteAndEnrichCompletionHistory(
            $stalePendingIds,
            $approval,
            $process,
            $current['step']
        );

        // Unlike Case A, the snapshot proves there is no next sequential step.
        // The process can therefore be completed and the fully approved request
        // can retain the status the normal final workflow would have produced.
        DB::table('processes')
            ->where('id', $process->id)
            ->where('status', 'in_progress')
            ->update(['status' => 'completed']);

        DB::table('attachment_requests')
            ->where('id', $request->id)
            ->where('status', 'pending')
            ->update(['status' => 'approved']);
    }

    /**
     * @return array{index: int, step: object, snapshot: array<string, mixed>}|null
     */
    private function missingAdvanceCurrentStep($steps, array $snapshotSteps): ?array
    {
        $orderedSteps = $steps
            ->sortBy('template_step_order')
            ->values();

        $pendingSteps = $orderedSteps
            ->where('status', 'pending')
            ->values();

        if ($pendingSteps->count() !== 1) {
            return null;
        }

        $currentStep = $pendingSteps->first();
        $currentIndex = null;

        foreach ($snapshotSteps as $index => $snapshotStep) {
            if ($this->snapshotMatchesStep($snapshotStep, $currentStep)) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex === null || $orderedSteps->count() !== $currentIndex + 1) {
            return null;
        }

        foreach ($orderedSteps as $index => $step) {
            $snapshotStep = $snapshotSteps[$index] ?? null;

            if (
                ! is_array($snapshotStep)
                || ! $this->snapshotMatchesStep($snapshotStep, $step)
                || ! $this->stepMatchesSnapshotAssignees($step, $snapshotStep)
            ) {
                return null;
            }

            if ($index < $currentIndex && $step->status !== 'approved') {
                return null;
            }

            if (
                $index === $currentIndex
                && (
                    $step->status !== 'pending'
                    || $this->stringValue($step->action_by) !== null
                    || $step->acted_at !== null
                )
            ) {
                return null;
            }
        }

        return [
            'index' => $currentIndex,
            'step' => $currentStep,
            'snapshot' => $snapshotSteps[$currentIndex],
        ];
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function validatedSnapshotSteps(array $snapshot): ?array
    {
        $validated = [];
        $identityKeys = [];
        $previousOrder = null;

        foreach (array_values($snapshot) as $stepConfig) {
            if (! is_array($stepConfig)) {
                return null;
            }

            $stepId = $this->stringValue($stepConfig['step_id'] ?? null);
            $templateStepOrder = $this->stringValue($stepConfig['template_step_order'] ?? null);
            $assignedUserId = $this->stringValue($stepConfig['assigned_user_id'] ?? null);
            $authorizedUserIds = $this->normalizedUserIds($stepConfig['authorized_user_ids'] ?? null);

            if (
                $stepId === null
                || $templateStepOrder === null
                || $assignedUserId === null
                || $authorizedUserIds === []
                || ! in_array($assignedUserId, $authorizedUserIds, true)
                || ! ctype_digit($templateStepOrder)
                || ($previousOrder !== null && (int) $templateStepOrder <= $previousOrder)
            ) {
                return null;
            }

            $identity = $stepId.'|'.$templateStepOrder;
            if (isset($identityKeys[$identity])) {
                return null;
            }

            $identityKeys[$identity] = true;
            $previousOrder = (int) $templateStepOrder;
            $stepConfig['authorized_user_ids'] = $authorizedUserIds;
            $validated[] = $stepConfig;
        }

        return $validated === [] ? null : $validated;
    }

    private function stepMatchesSnapshotAssignees(object $step, array $snapshot): bool
    {
        $assignedUserId = $this->stringValue($step->assigned_user_id);
        $snapshotAssignedUserId = $this->stringValue($snapshot['assigned_user_id'] ?? null);
        $stepAuthorizedUserIds = $this->normalizedUserIds($step->authorized_user_ids ?? null);
        $snapshotAuthorizedUserIds = $this->normalizedUserIds($snapshot['authorized_user_ids'] ?? null);

        return $assignedUserId !== null
            && $assignedUserId === $snapshotAssignedUserId
            && $stepAuthorizedUserIds !== []
            && $stepAuthorizedUserIds === $snapshotAuthorizedUserIds;
    }

    private function approvedItemForHistory(object $approval, $items): ?object
    {
        $metadata = $this->decodeJson($approval->metadata);
        $linkedItemId = $this->stringValue($approval->attachment_request_item_id);
        $metadataItemId = $this->stringValue($metadata['item_id'] ?? null);

        if (
            ($linkedItemId === null && $metadataItemId === null)
            || ($linkedItemId !== null && $metadataItemId !== null && $linkedItemId !== $metadataItemId)
        ) {
            return null;
        }

        $item = $items->get($linkedItemId ?? $metadataItemId);

        return $item !== null
            && $item->status === 'approved'
            && $this->stringValue($item->responded_by_user_id) !== null
            && $item->responded_at !== null
            ? $item
            : null;
    }

    private function approvalProvesCurrentStepCompletion(
        object $approval,
        object $item,
        object $process,
        object $currentStep,
        array $currentSnapshot
    ): bool {
        $actorId = $this->stringValue($approval->user_id);
        $metadata = $this->decodeJson($approval->metadata);

        if (
            $actorId === null
            || $actorId !== $this->stringValue($item->responded_by_user_id)
            || ! in_array($actorId, $this->normalizedUserIds($currentStep->authorized_user_ids ?? null), true)
            || ! $this->stepMatchesSnapshotAssignees($currentStep, $currentSnapshot)
        ) {
            return false;
        }

        return $this->optionalMetadataMatches($metadata, 'process_id', (string) $process->id)
            && $this->optionalMetadataMatches($metadata, 'process_step_id', (string) $currentStep->id)
            && $this->optionalMetadataMatches($metadata, 'step_id', (string) $currentStep->step_id)
            && $this->optionalMetadataMatches(
                $metadata,
                'template_step_order',
                (string) $currentStep->template_step_order
            )
            && (($metadata['status'] ?? 'approved') === 'approved');
    }

    private function optionalMetadataMatches(array $metadata, string $key, string $expected): bool
    {
        $value = $this->stringValue($metadata[$key] ?? null);

        return $value === null || $value === $expected;
    }

    private function isRestorableNextPendingHistory(object $history, object $process, array $snapshot): bool
    {
        $metadata = $this->decodeJson($history->metadata);
        $assignedUserId = $this->stringValue($metadata['assigned_user_id'] ?? null);

        return ($metadata['status'] ?? null) === 'pending'
            && $this->stringValue($metadata['process_id'] ?? null) === (string) $process->id
            && $this->stringValue($metadata['process_sort_order'] ?? null) === (string) ($process->sort_order ?? 0)
            && $this->stringValue($metadata['process_step_id'] ?? null) === null
            && $this->stringValue($metadata['step_id'] ?? null) === $this->stringValue($snapshot['step_id'] ?? null)
            && $this->stringValue($metadata['template_step_order'] ?? null) === $this->stringValue($snapshot['template_step_order'] ?? null)
            && $assignedUserId !== null
            && $assignedUserId === $this->stringValue($snapshot['assigned_user_id'] ?? null)
            && $this->normalizedUserIds($metadata['authorized_user_ids'] ?? null)
                === $this->normalizedUserIds($snapshot['authorized_user_ids'] ?? null);
    }

    private function snapshotStepExists(array $snapshot): bool
    {
        $stepId = $this->stringValue($snapshot['step_id'] ?? null);

        if ($stepId === null) {
            return false;
        }

        if (! Schema::hasTable('procedure_setting_steps')) {
            return true;
        }

        return DB::table('procedure_setting_steps')
            ->where('id', $stepId)
            ->exists();
    }

    private function hasPersistedNextStepConflict($steps, array $snapshot): bool
    {
        return $steps->contains(function ($step) use ($snapshot): bool {
            return $this->snapshotMatchesStep($snapshot, $step)
                || $this->stringValue($step->step_id) === $this->stringValue($snapshot['step_id'] ?? null)
                || $this->stringValue($step->template_step_order)
                    === $this->stringValue($snapshot['template_step_order'] ?? null);
        });
    }

    private function hasConflictingNextPendingHistory($history, object $process, array $snapshot): bool
    {
        foreach ($history->where('action', 'workflow_step_pending') as $pendingHistory) {
            $metadata = $this->decodeJson($pendingHistory->metadata);
            $historyProcessId = $this->stringValue($metadata['process_id'] ?? null);

            if (
                ($metadata['status'] ?? null) !== 'pending'
                || ! in_array($historyProcessId, [null, (string) $process->id], true)
                || $this->stringValue($metadata['step_id'] ?? null)
                    !== $this->stringValue($snapshot['step_id'] ?? null)
                || $this->stringValue($metadata['template_step_order'] ?? null)
                    !== $this->stringValue($snapshot['template_step_order'] ?? null)
            ) {
                continue;
            }

            if (! $this->isRestorableNextPendingHistory($pendingHistory, $process, $snapshot)) {
                return true;
            }
        }

        return false;
    }

    private function markCurrentStepApproved(object $step, string $actorId, string $actedAt): void
    {
        DB::table('process_steps')
            ->where('id', $step->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'action_by' => $actorId,
                'acted_at' => $actedAt,
                'updated_at' => $actedAt,
            ]);
    }

    private function restorePendingStep(
        string $stepId,
        object $process,
        array $snapshot,
        string $pendingAt
    ): void {
        DB::table('process_steps')->insert([
            'id' => $stepId,
            'process_id' => (string) $process->id,
            'step_id' => $snapshot['step_id'],
            'template_step_order' => $snapshot['template_step_order'],
            'assigned_user_id' => $snapshot['assigned_user_id'],
            'authorized_user_ids' => $this->encodeJson($snapshot['authorized_user_ids']),
            'escalation_management_hierarchy_id' => $snapshot['escalation_management_hierarchy_id'] ?? null,
            'status' => 'pending',
            'action_by' => null,
            'acted_at' => null,
            'created_at' => $pendingAt,
            'updated_at' => $pendingAt,
        ]);
    }

    private function createPendingHistoryFromSnapshot(
        object $request,
        object $process,
        string $processStepId,
        array $snapshot,
        string $createdAt
    ): void {
        $updates = $this->restoredPendingHistoryUpdate($process, $processStepId, $snapshot);

        DB::table('attachment_request_history')->insert([
            'id' => (string) Str::uuid(),
            'attachment_request_id' => (string) $request->id,
            'attachment_request_item_id' => null,
            'action' => 'workflow_step_pending',
            'description' => 'Workflow step pending',
            // The immutable snapshot names the assignee; no user is inferred
            // from mutable procedure configuration.
            'user_id' => $snapshot['assigned_user_id'],
            'metadata' => $updates['metadata'],
            'sort_order' => $updates['sort_order'],
            'created_at' => $createdAt,
        ]);
    }

    /**
     * @return array{metadata: string, sort_order: int}
     */
    private function restoredPendingHistoryUpdate(object $process, string $processStepId, array $snapshot): array
    {
        return [
            'metadata' => $this->encodeJson([
                'process_id' => (string) $process->id,
                'process_sort_order' => (int) ($process->sort_order ?? 0),
                'process_step_id' => $processStepId,
                'step_id' => $snapshot['step_id'],
                'template_step_order' => $snapshot['template_step_order'],
                'assigned_user_id' => $snapshot['assigned_user_id'],
                'authorized_user_ids' => $snapshot['authorized_user_ids'],
                'status' => 'pending',
                'acted_at' => null,
                'is_auto_approved' => false,
            ]),
            'sort_order' => $this->workflowSortOrderFromSnapshot($process, $snapshot),
        ];
    }

    private function deleteAndEnrichCompletionHistory(
        array $stalePendingIds,
        object $approval,
        object $process,
        object $step
    ): void {
        DB::table('attachment_request_history')
            ->whereIn('id', $stalePendingIds)
            ->delete();

        DB::table('attachment_request_history')
            ->where('id', $approval->id)
            ->update($this->attachmentApprovalUpdate($approval, $process, $step));
    }

    private function allItemsApproved($items): bool
    {
        return $items->isNotEmpty()
            && $items->every(static fn ($item): bool => $item->status === 'approved');
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

    private function workflowSortOrderFromSnapshot(object $process, array $snapshot): int
    {
        return 100000
            + ((int) ($process->sort_order ?? 0) * 1000)
            + (int) $snapshot['template_step_order'];
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
            ->filter(static fn ($step): bool => (string) $step->step_id === $stepId
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

        $historyProcessId = $this->stringValue($metadata['process_id'] ?? null);
        if ($historyProcessId !== null && $historyProcessId !== $processId) {
            return false;
        }

        $processStepId = $this->stringValue($metadata['process_step_id'] ?? null);
        if ($processStepId !== null) {
            return $processStepId === (string) $approvedStep->id;
        }

        return $historyProcessId === $processId
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

    private function encodeJson(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<string>
     */
    private function normalizedUserIds(mixed $value): array
    {
        $ids = $this->decodeJson($value);
        $ids = array_values(array_unique(array_filter(
            array_map(fn ($id): ?string => $this->stringValue($id), $ids),
            static fn (?string $id): bool => $id !== null
        )));
        sort($ids, SORT_STRING);

        return $ids;
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
            && Schema::hasTable('attachment_request_items')
            && Schema::hasTable('processes')
            && Schema::hasTable('process_steps')
            && Schema::hasTable('attachment_request_history')
            && Schema::hasColumn('attachment_requests', 'id')
            && Schema::hasColumn('attachment_requests', 'status')
            && Schema::hasColumn('attachment_request_items', 'id')
            && Schema::hasColumn('attachment_request_items', 'attachment_request_id')
            && Schema::hasColumn('attachment_request_items', 'status')
            && Schema::hasColumn('attachment_request_items', 'responded_by_user_id')
            && Schema::hasColumn('attachment_request_items', 'responded_at')
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
            && Schema::hasColumn('process_steps', 'assigned_user_id')
            && Schema::hasColumn('process_steps', 'authorized_user_ids')
            && Schema::hasColumn('process_steps', 'status')
            && Schema::hasColumn('process_steps', 'action_by')
            && Schema::hasColumn('process_steps', 'acted_at')
            && Schema::hasColumn('process_steps', 'created_at')
            && Schema::hasColumn('process_steps', 'updated_at')
            && Schema::hasColumn('attachment_request_history', 'id')
            && Schema::hasColumn('attachment_request_history', 'attachment_request_id')
            && Schema::hasColumn('attachment_request_history', 'action')
            && Schema::hasColumn('attachment_request_history', 'user_id')
            && Schema::hasColumn('attachment_request_history', 'metadata')
            && Schema::hasColumn('attachment_request_history', 'sort_order');
    }
};
