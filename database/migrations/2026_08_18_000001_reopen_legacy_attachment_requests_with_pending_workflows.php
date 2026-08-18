<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_CUTOFF = '2026-08-14 00:00:00';

    private const PROCESSABLE_TYPE = 'attachment_request';

    public function up(): void
    {
        if (! $this->hasRequiredSchema()) {
            return;
        }

        do {
            $requestIds = DB::table('attachment_requests as requests')
                ->join('processes as processes', function ($join): void {
                    $join->on('processes.processable_id', '=', 'requests.id')
                        ->where('processes.processable_type', self::PROCESSABLE_TYPE);
                })
                ->where('requests.created_at', '<', self::LEGACY_CUTOFF)
                ->where('requests.status', 'approved')
                ->whereIn('processes.status', ['pending', 'in_progress'])
                ->whereExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('process_steps')
                        ->whereColumn('process_steps.process_id', 'processes.id')
                        ->where('process_steps.status', 'pending');
                })
                ->whereExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('attachment_request_history as history')
                        ->whereColumn('history.attachment_request_id', 'requests.id')
                        ->where('history.action', 'workflow_step_pending');
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

            $this->removeStalePendingHistory($requestIds);

            DB::table('attachment_requests')
                ->whereIn('id', $requestIds)
                ->where('status', 'approved')
                ->update(['status' => 'pending']);
        } while (count($requestIds) === 500);
    }

    public function down(): void
    {
        // Neither the former status nor deleted stale history can be reconstructed safely.
    }

    /**
     * @param  list<string>  $requestIds
     */
    private function removeStalePendingHistory(array $requestIds): void
    {
        $approvedSteps = DB::table('process_steps as steps')
            ->join('processes as processes', 'processes.id', '=', 'steps.process_id')
            ->where('processes.processable_type', self::PROCESSABLE_TYPE)
            ->whereIn('processes.processable_id', $requestIds)
            ->where('steps.status', 'approved')
            ->select([
                'steps.id',
                'steps.process_id',
                'steps.step_id',
                'steps.template_step_order',
            ])
            ->get();

        $approvedStepIds = [];
        $approvedStepIdsByIdentity = [];

        foreach ($approvedSteps as $step) {
            $approvedStepIds[(string) $step->id] = true;

            if ($step->step_id === null || $step->template_step_order === null) {
                continue;
            }

            $approvedStepIdsByIdentity[$this->stepIdentity(
                (string) $step->process_id,
                (string) $step->step_id,
                (string) $step->template_step_order
            )][] = (string) $step->id;
        }

        if ($approvedStepIds === []) {
            return;
        }

        DB::table('attachment_request_history')
            ->whereIn('attachment_request_id', $requestIds)
            ->where('action', 'workflow_step_pending')
            ->select(['id', 'metadata'])
            ->orderBy('id')
            ->chunkById(500, function ($historyRows) use ($approvedStepIds, $approvedStepIdsByIdentity): void {
                $staleHistoryIds = [];

                foreach ($historyRows as $historyRow) {
                    $metadata = $this->decodeJson($historyRow->metadata);

                    if ($this->referencesApprovedStep($metadata, $approvedStepIds, $approvedStepIdsByIdentity)) {
                        $staleHistoryIds[] = $historyRow->id;
                    }
                }

                if ($staleHistoryIds !== []) {
                    DB::table('attachment_request_history')
                        ->whereIn('id', $staleHistoryIds)
                        ->delete();
                }
            }, 'id');
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, true>  $approvedStepIds
     * @param  array<string, list<string>>  $approvedStepIdsByIdentity
     */
    private function referencesApprovedStep(
        array $metadata,
        array $approvedStepIds,
        array $approvedStepIdsByIdentity
    ): bool {
        $processStepId = $this->stringValue($metadata['process_step_id'] ?? null);

        if ($processStepId !== null) {
            return isset($approvedStepIds[$processStepId]);
        }

        $processId = $this->stringValue($metadata['process_id'] ?? null);
        $stepId = $this->stringValue($metadata['step_id'] ?? null);
        $templateStepOrder = $this->stringValue($metadata['template_step_order'] ?? null);

        if ($processId === null || $stepId === null || $templateStepOrder === null) {
            return false;
        }

        return count($approvedStepIdsByIdentity[$this->stepIdentity(
            $processId,
            $stepId,
            $templateStepOrder
        )] ?? []) === 1;
    }

    private function stepIdentity(string $processId, string $stepId, string $templateStepOrder): string
    {
        return $processId.'|'.$stepId.'|'.$templateStepOrder;
    }

    /**
     * @return array<string, mixed>
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
            && Schema::hasColumn('attachment_requests', 'created_at')
            && Schema::hasColumn('attachment_requests', 'status')
            && Schema::hasColumn('processes', 'processable_id')
            && Schema::hasColumn('processes', 'processable_type')
            && Schema::hasColumn('processes', 'id')
            && Schema::hasColumn('processes', 'status')
            && Schema::hasColumn('process_steps', 'id')
            && Schema::hasColumn('process_steps', 'process_id')
            && Schema::hasColumn('process_steps', 'step_id')
            && Schema::hasColumn('process_steps', 'template_step_order')
            && Schema::hasColumn('process_steps', 'status')
            && Schema::hasColumn('attachment_request_history', 'id')
            && Schema::hasColumn('attachment_request_history', 'attachment_request_id')
            && Schema::hasColumn('attachment_request_history', 'action')
            && Schema::hasColumn('attachment_request_history', 'metadata');
    }
};
