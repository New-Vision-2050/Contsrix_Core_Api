<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const PROCESSABLE_TYPE = 'attachment_request';

    /**
     * The only Attachment Requests this production-data repair is permitted to
     * inspect or change. These UUIDs were taken from the supplied production
     * API response; serial numbers are deliberately not used as selectors.
     *
     * @var list<string>
     */
    private const GROUP_A_REQUEST_IDS = [
        '529fdf4d-7fee-4105-b079-29bc0e8a063a',
        '14829a79-60f3-4d39-b36f-cfa3021c687c',
    ];

    /**
     * @var list<string>
     */
    private const GROUP_B_REQUEST_IDS = [
        '0f714ab2-2f64-4d05-ab81-6a033722466b',
        'e663a038-6e49-424d-b7f8-96891e16f10b',
        '81390d0d-d9d9-46a9-8c27-8193e7d66681',
        '47349dd1-e1d3-464e-b186-1cc9ee9da92e',
        'd59bdd29-5844-4715-8e0d-4ff32aa2a85d',
        '0f343c6b-d9c8-4503-bdd8-3353e4b726a2',
        '38df8d13-e3ee-4620-8f49-e06e7643b9f9',
        '3c6d518b-2d0f-4beb-9a8c-88f8622ccab5',
    ];

    public function up(): void
    {
        if (! $this->hasRequiredSchema()) {
            return;
        }

        foreach (self::GROUP_A_REQUEST_IDS as $requestId) {
            DB::transaction(function () use ($requestId): void {
                $this->repairGroupA($requestId);
            });
        }

        foreach (self::GROUP_B_REQUEST_IDS as $requestId) {
            DB::transaction(function () use ($requestId): void {
                $this->repairGroupB($requestId);
            });
        }
    }

    public function down(): void
    {
        // This repair deletes stale lifecycle history. Restoring it would require
        // inventing historical data, so reversal is intentionally unsafe.
    }

    private function repairGroupA(string $requestId): void
    {
        $state = $this->brokenState($requestId, groupA: true);
        if ($state === null) {
            return;
        }

        $thirdStep = $state['third_step'];
        if ($thirdStep === null) {
            $snapshot = $state['third_snapshot'];

            // The existing Step 3 history is the real activation timestamp. Do
            // not fabricate one while recreating only the missing ProcessStep.
            if (! $this->snapshotStepExists($snapshot)) {
                return;
            }

            $thirdStepId = (string) Str::uuid();
            $thirdStep = (object) [
                'id' => $thirdStepId,
                'step_id' => $snapshot['step_id'],
                'template_step_order' => $snapshot['template_step_order'],
                'assigned_user_id' => $snapshot['assigned_user_id'],
                'authorized_user_ids' => $this->encodeJson($snapshot['authorized_user_ids']),
                'status' => 'pending',
                'action_by' => null,
                'acted_at' => null,
            ];

            DB::table('process_steps')->insert([
                'id' => $thirdStepId,
                'process_id' => $state['process']->id,
                'step_id' => $snapshot['step_id'],
                'template_step_order' => $snapshot['template_step_order'],
                'assigned_user_id' => $snapshot['assigned_user_id'],
                'authorized_user_ids' => $this->encodeJson($snapshot['authorized_user_ids']),
                'escalation_management_hierarchy_id' => $snapshot['escalation_management_hierarchy_id'] ?? null,
                'status' => 'pending',
                'action_by' => null,
                'acted_at' => null,
                'created_at' => $state['third_pending_history']->created_at,
                'updated_at' => $state['third_pending_history']->created_at,
            ]);
        }

        $this->approveStepTwo($state);
        $this->enrichApprovalHistory($state);
        $this->linkThirdPendingHistory($state, $thirdStep);
        $this->deleteStalePendingHistory($state);
    }

    private function repairGroupB(string $requestId): void
    {
        $state = $this->brokenState($requestId, groupA: false);
        if ($state === null) {
            return;
        }

        $this->approveStepTwo($state);
        $this->enrichApprovalHistory($state);
        $this->deleteStalePendingHistory($state);

        // Group B is an explicitly supplied terminal repair. It deliberately
        // does not activate or create a future workflow step.
        DB::table('processes')
            ->where('id', $state['process']->id)
            ->where('status', 'in_progress')
            ->update(['status' => 'completed']);

        DB::table('attachment_requests')
            ->where('id', $state['request']->id)
            ->where('status', 'pending')
            ->update(['status' => 'approved']);
    }

    /**
     * Load and validate the documented broken state for one explicitly
     * allowlisted request. No request other than $requestId is queried.
     *
     * @return array{
     *   request: object,
     *   process: object,
     *   step_one: object,
     *   step_two: object,
     *   third_step: object|null,
     *   third_snapshot: array<string, mixed>|null,
     *   item: object,
     *   approval_history: object,
     *   stale_pending_history: object,
     *   third_pending_history: object|null
     * }|null
     */
    private function brokenState(string $requestId, bool $groupA): ?array
    {
        $request = DB::table('attachment_requests')
            ->where('id', $requestId)
            ->lockForUpdate()
            ->first();

        if ($request === null || $request->status !== 'pending') {
            return null;
        }

        $processes = DB::table('processes')
            ->where('processable_id', $requestId)
            ->where('processable_type', self::PROCESSABLE_TYPE)
            ->lockForUpdate()
            ->get();

        if ($processes->count() !== 1) {
            return null;
        }

        $process = $processes->first();
        if ($process->execute_type !== 'sequence' || $process->status !== 'in_progress') {
            return null;
        }

        $steps = DB::table('process_steps')
            ->where('process_id', $process->id)
            ->orderBy('template_step_order')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $stepOne = $this->singleStep($steps, 1);
        $stepTwo = $this->singleStep($steps, 2);
        if (
            $stepOne === null
            || $stepTwo === null
            || $stepOne->status !== 'approved'
            || $this->stringValue($stepOne->action_by) === null
            || $stepOne->acted_at === null
            || $stepTwo->status !== 'pending'
            || $this->stringValue($stepTwo->action_by) !== null
            || $stepTwo->acted_at !== null
            || $this->stringValue($stepTwo->step_id) === null
            || $this->stringValue($stepTwo->assigned_user_id) === null
        ) {
            return null;
        }

        $items = DB::table('attachment_request_items')
            ->where('attachment_request_id', $requestId)
            ->lockForUpdate()
            ->get()
            ->keyBy(static fn (object $item): string => (string) $item->id);

        $history = DB::table('attachment_request_history')
            ->where('attachment_request_id', $requestId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($history->where('action', 'request_created')->count() !== 1) {
            return null;
        }

        $stepOneHistory = $history
            ->filter(fn (object $historyRow): bool => $this->workflowHistoryMatches(
                $historyRow,
                $process,
                $stepOne,
                'workflow_step_approved',
                'approved',
                allowUnlinkedStep: false,
            ))
            ->values();

        if (
            $stepOneHistory->count() !== 1
            || $this->stringValue($stepOneHistory->first()->user_id) !== $this->stringValue($stepOne->action_by)
        ) {
            return null;
        }

        $approvalAndItem = $this->approvalAndItem($history, $items, $process);
        if ($approvalAndItem === null) {
            return null;
        }

        $approval = $approvalAndItem['approval'];
        $item = $approvalAndItem['item'];
        $approvalActorId = $this->stringValue($approval->user_id);

        if (
            $approvalActorId === null
            || ! in_array($approvalActorId, $this->normalizedUserIds($stepTwo->authorized_user_ids), true)
        ) {
            return null;
        }

        $stalePendingHistory = $history
            ->filter(fn (object $historyRow): bool => $this->workflowHistoryMatches(
                $historyRow,
                $process,
                $stepTwo,
                'workflow_step_pending',
                'pending',
                allowUnlinkedStep: false,
            ))
            ->values();

        if (
            $stalePendingHistory->count() !== 1
            || $this->stringValue($stalePendingHistory->first()->user_id) !== $approvalActorId
        ) {
            return null;
        }

        if (! $groupA) {
            if (
                $steps->count() !== 2
                || $this->hasWorkflowHistoryAtOrAfterOrder($history, $process, 3)
                || ! $this->allItemsApproved($items)
                || $history->where('action', 'request_approved')->isNotEmpty()
            ) {
                return null;
            }

            return [
                'request' => $request,
                'process' => $process,
                'step_one' => $stepOne,
                'step_two' => $stepTwo,
                'third_step' => null,
                'third_snapshot' => null,
                'item' => $item,
                'approval_history' => $approval,
                'stale_pending_history' => $stalePendingHistory->first(),
                'third_pending_history' => null,
            ];
        }

        $snapshots = $this->snapshotByOrder($process->template_snapshot);
        $thirdSnapshot = $snapshots[3] ?? null;
        if (
            ! $this->snapshotMatchesStep($snapshots[1] ?? null, $stepOne)
            || ! $this->snapshotMatchesStep($snapshots[2] ?? null, $stepTwo)
            || $thirdSnapshot === null
            || ! $this->stepMatchesSnapshotAssignees($stepOne, $snapshots[1])
            || ! $this->stepMatchesSnapshotAssignees($stepTwo, $snapshots[2])
        ) {
            return null;
        }

        $thirdStep = $this->singleStep($steps, 3);
        if (
            $steps->count() !== ($thirdStep === null ? 2 : 3)
            || ($thirdStep !== null && ! $this->isValidPendingThirdStep($thirdStep, $thirdSnapshot))
        ) {
            return null;
        }

        $thirdPendingHistory = $history
            ->filter(fn (object $historyRow): bool => $this->pendingHistoryMatchesSnapshot(
                $historyRow,
                $process,
                $thirdSnapshot,
                $thirdStep,
            ))
            ->values();

        if ($thirdPendingHistory->count() !== 1) {
            return null;
        }

        return [
            'request' => $request,
            'process' => $process,
            'step_one' => $stepOne,
            'step_two' => $stepTwo,
            'third_step' => $thirdStep,
            'third_snapshot' => $thirdSnapshot,
            'item' => $item,
            'approval_history' => $approval,
            'stale_pending_history' => $stalePendingHistory->first(),
            'third_pending_history' => $thirdPendingHistory->first(),
        ];
    }

    /**
     * @param  Collection<int, object>  $history
     * @param  Collection<string, object>  $items
     * @return array{approval: object, item: object}|null
     */
    private function approvalAndItem($history, $items, object $process): ?array
    {
        $approvals = $history->where('action', 'attachment_approved')->values();
        if ($approvals->count() !== 1) {
            return null;
        }

        $approval = $approvals->first();
        $itemId = $this->stringValue($approval->attachment_request_item_id);
        $actorId = $this->stringValue($approval->user_id);
        $metadata = $this->decodeJson($approval->metadata);
        $item = $itemId === null ? null : $items->get($itemId);

        if (
            $item === null
            || $actorId === null
            || $item->status !== 'approved'
            || $this->stringValue($item->responded_by_user_id) !== $actorId
            || $item->responded_at === null
            || ($metadata['status'] ?? null) !== 'approved'
            || $this->stringValue($metadata['item_id'] ?? null) !== $itemId
            || $this->stringValue($metadata['process_id'] ?? null) !== (string) $process->id
        ) {
            return null;
        }

        return compact('approval', 'item');
    }

    private function approveStepTwo(array $state): void
    {
        $itemApprovalTime = (string) $state['item']->responded_at;

        DB::table('process_steps')
            ->where('id', $state['step_two']->id)
            ->where('process_id', $state['process']->id)
            ->where('status', 'pending')
            ->whereNull('action_by')
            ->whereNull('acted_at')
            ->update([
                'status' => 'approved',
                'action_by' => $state['approval_history']->user_id,
                'acted_at' => $itemApprovalTime,
                'updated_at' => $itemApprovalTime,
            ]);
    }

    private function enrichApprovalHistory(array $state): void
    {
        $approval = $state['approval_history'];
        $step = $state['step_two'];
        $metadata = $this->decodeJson($approval->metadata);
        $metadata['process_id'] = (string) $state['process']->id;
        $metadata['process_sort_order'] = (int) ($state['process']->sort_order ?? 0);
        $metadata['process_step_id'] = (string) $step->id;
        $metadata['step_id'] = (int) $step->step_id;
        $metadata['template_step_order'] = (int) $step->template_step_order;
        $metadata['assigned_user_id'] = (string) $step->assigned_user_id;
        $metadata['authorized_user_ids'] = $this->normalizedUserIds($step->authorized_user_ids);
        $metadata['status'] = 'approved';
        $metadata['acted_at'] = $this->isoUtc((string) $state['item']->responded_at);
        $metadata['is_auto_approved'] = false;

        DB::table('attachment_request_history')
            ->where('id', $approval->id)
            ->where('attachment_request_id', $state['request']->id)
            ->where('action', 'attachment_approved')
            ->update([
                'metadata' => $this->encodeJson($metadata),
                'dedupe_key' => $this->attachmentApprovalDedupeKey($state),
                'sort_order' => $this->workflowSortOrder($state['process'], $step),
            ]);
    }

    private function linkThirdPendingHistory(array $state, object $thirdStep): void
    {
        $history = $state['third_pending_history'];
        $metadata = $this->decodeJson($history->metadata);
        $metadata['process_id'] = (string) $state['process']->id;
        $metadata['process_sort_order'] = (int) ($state['process']->sort_order ?? 0);
        $metadata['process_step_id'] = (string) $thirdStep->id;
        $metadata['step_id'] = (int) $thirdStep->step_id;
        $metadata['template_step_order'] = (int) $thirdStep->template_step_order;
        $metadata['assigned_user_id'] = (string) $thirdStep->assigned_user_id;
        $metadata['authorized_user_ids'] = $this->normalizedUserIds($thirdStep->authorized_user_ids);
        $metadata['status'] = 'pending';
        $metadata['acted_at'] = null;
        $metadata['is_auto_approved'] = false;

        DB::table('attachment_request_history')
            ->where('id', $history->id)
            ->where('attachment_request_id', $state['request']->id)
            ->where('action', 'workflow_step_pending')
            ->update([
                'metadata' => $this->encodeJson($metadata),
                'dedupe_key' => $this->workflowDedupeKey($state['request']->id, $state['process'], $thirdStep),
                'sort_order' => $this->workflowSortOrder($state['process'], $thirdStep),
            ]);
    }

    private function deleteStalePendingHistory(array $state): void
    {
        DB::table('attachment_request_history')
            ->where('id', $state['stale_pending_history']->id)
            ->where('attachment_request_id', $state['request']->id)
            ->where('action', 'workflow_step_pending')
            ->delete();
    }

    private function singleStep($steps, int $order): ?object
    {
        $matching = $steps
            ->filter(static fn (object $step): bool => (int) $step->template_step_order === $order)
            ->values();

        return $matching->count() === 1 ? $matching->first() : null;
    }

    private function workflowHistoryMatches(
        object $history,
        object $process,
        object $step,
        string $action,
        string $status,
        bool $allowUnlinkedStep,
    ): bool {
        $metadata = $this->decodeJson($history->metadata);
        $processStepId = $this->stringValue($metadata['process_step_id'] ?? null);

        return $history->action === $action
            && ($metadata['status'] ?? null) === $status
            && $this->stringValue($metadata['process_id'] ?? null) === (string) $process->id
            && $this->stringValue($metadata['step_id'] ?? null) === (string) $step->step_id
            && $this->stringValue($metadata['template_step_order'] ?? null) === (string) $step->template_step_order
            && ($allowUnlinkedStep ? $processStepId === null || $processStepId === (string) $step->id : $processStepId === (string) $step->id)
            && $this->stringValue($metadata['assigned_user_id'] ?? null) === (string) $step->assigned_user_id
            && $this->normalizedUserIds($metadata['authorized_user_ids'] ?? null)
                === $this->normalizedUserIds($step->authorized_user_ids);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function pendingHistoryMatchesSnapshot(
        object $history,
        object $process,
        array $snapshot,
        ?object $thirdStep,
    ): bool {
        $metadata = $this->decodeJson($history->metadata);
        $processStepId = $this->stringValue($metadata['process_step_id'] ?? null);

        return $history->action === 'workflow_step_pending'
            && ($metadata['status'] ?? null) === 'pending'
            && $this->stringValue($metadata['process_id'] ?? null) === (string) $process->id
            && $this->stringValue($metadata['step_id'] ?? null) === $this->stringValue($snapshot['step_id'] ?? null)
            && $this->stringValue($metadata['template_step_order'] ?? null)
                === $this->stringValue($snapshot['template_step_order'] ?? null)
            && $this->stringValue($metadata['assigned_user_id'] ?? null)
                === $this->stringValue($snapshot['assigned_user_id'] ?? null)
            && $this->normalizedUserIds($metadata['authorized_user_ids'] ?? null)
                === $this->normalizedUserIds($snapshot['authorized_user_ids'] ?? null)
            && $this->stringValue($history->user_id) === $this->stringValue($snapshot['assigned_user_id'] ?? null)
            && ($thirdStep === null ? $processStepId === null : $processStepId === null || $processStepId === (string) $thirdStep->id);
    }

    private function isValidPendingThirdStep(object $step, array $snapshot): bool
    {
        return $step->status === 'pending'
            && $this->stringValue($step->action_by) === null
            && $step->acted_at === null
            && $this->snapshotMatchesStep($snapshot, $step)
            && $this->stepMatchesSnapshotAssignees($step, $snapshot);
    }

    private function hasWorkflowHistoryAtOrAfterOrder($history, object $process, int $order): bool
    {
        return $history->contains(function (object $historyRow) use ($process, $order): bool {
            if (! in_array($historyRow->action, [
                'workflow_step_pending',
                'workflow_step_approved',
                'workflow_step_rejected',
            ], true)) {
                return false;
            }

            $metadata = $this->decodeJson($historyRow->metadata);

            return $this->stringValue($metadata['process_id'] ?? null) === (string) $process->id
                && (int) ($metadata['template_step_order'] ?? 0) >= $order;
        });
    }

    private function allItemsApproved($items): bool
    {
        return $items->isNotEmpty()
            && $items->every(static fn (object $item): bool => $item->status === 'approved');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function snapshotByOrder(mixed $value): array
    {
        $snapshots = [];
        foreach ($this->decodeJson($value) as $snapshot) {
            if (! is_array($snapshot)) {
                return [];
            }

            $order = $snapshot['template_step_order'] ?? null;
            if (! is_int($order) && ! (is_string($order) && ctype_digit($order))) {
                return [];
            }

            $order = (int) $order;
            if (isset($snapshots[$order])) {
                return [];
            }

            $snapshot['authorized_user_ids'] = $this->normalizedUserIds($snapshot['authorized_user_ids'] ?? null);
            if (
                $order < 1
                || $this->stringValue($snapshot['step_id'] ?? null) === null
                || $this->stringValue($snapshot['assigned_user_id'] ?? null) === null
                || $snapshot['authorized_user_ids'] === []
            ) {
                return [];
            }

            $snapshots[$order] = $snapshot;
        }

        return $snapshots;
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     */
    private function snapshotMatchesStep(?array $snapshot, object $step): bool
    {
        return $snapshot !== null
            && $this->stringValue($snapshot['step_id'] ?? null) === $this->stringValue($step->step_id)
            && $this->stringValue($snapshot['template_step_order'] ?? null)
                === $this->stringValue($step->template_step_order);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function stepMatchesSnapshotAssignees(object $step, array $snapshot): bool
    {
        return $this->stringValue($step->assigned_user_id)
                === $this->stringValue($snapshot['assigned_user_id'] ?? null)
            && $this->normalizedUserIds($step->authorized_user_ids)
                === $this->normalizedUserIds($snapshot['authorized_user_ids'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function snapshotStepExists(array $snapshot): bool
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return true;
        }

        return DB::table('procedure_setting_steps')
            ->where('id', $snapshot['step_id'])
            ->exists();
    }

    private function workflowSortOrder(object $process, object $step): int
    {
        return 100000
            + ((int) ($process->sort_order ?? 0) * 1000)
            + (int) $step->template_step_order;
    }

    private function workflowDedupeKey(string $requestId, object $process, object $step): string
    {
        return hash('sha256', implode('|', [
            $requestId,
            'workflow_step',
            (string) $process->id,
            (string) $step->step_id,
            (string) $step->template_step_order,
        ]));
    }

    private function attachmentApprovalDedupeKey(array $state): string
    {
        return hash('sha256', implode('|', [
            (string) $state['request']->id,
            'attachment_approved',
            (string) $state['item']->id,
            (string) $state['process']->id,
            (string) $state['step_two']->step_id,
            (string) $state['step_two']->template_step_order,
        ]));
    }

    private function isoUtc(string $timestamp): string
    {
        return str_contains($timestamp, 'T')
            ? $timestamp
            : str_replace(' ', 'T', $timestamp).'+00:00';
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
            array_map(fn (mixed $id): ?string => $this->stringValue($id), $ids),
            static fn (?string $id): bool => $id !== null,
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
            && Schema::hasColumn('attachment_request_history', 'attachment_request_item_id')
            && Schema::hasColumn('attachment_request_history', 'action')
            && Schema::hasColumn('attachment_request_history', 'user_id')
            && Schema::hasColumn('attachment_request_history', 'metadata')
            && Schema::hasColumn('attachment_request_history', 'dedupe_key')
            && Schema::hasColumn('attachment_request_history', 'sort_order');
    }
};
