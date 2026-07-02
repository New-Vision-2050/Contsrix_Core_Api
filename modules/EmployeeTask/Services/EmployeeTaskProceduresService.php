<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;
use Modules\ProcedureSetting\Models\InternalProcedureTaken;
use Modules\Process\Models\Process;
use Modules\Process\Enums\ProcessStatus;
use Modules\Shared\Media\Models\CustomMedia;

final class EmployeeTaskProceduresService
{
    public function __construct(
        private readonly EmployeeTaskRepository $repository,
    ) {}

    /**
     * Return all taken procedures for a task, ordered by taken_at ascending,
     * together with a summary (total, last action name, start date, progress %).
     *
     * Each item is enriched with:
     *   - process: the related workflow Process (status, steps with approvers)
     *   - attachments: media files uploaded with this procedure
     *   - form_data: the submitted form metadata from the process
     *
     * @return array{items: list<array>, summary: array}
     */
    public function forTask(string $taskId): array
    {
        $task = $this->repository->findById($taskId);

        if (! $task) {
            throw EmployeeTaskException::notFound();
        }

        /** @var Collection<int, InternalProcedureTaken> $taken */
        $taken = InternalProcedureTaken::query()
            ->where('processable_type', $task->procedureSettingType()->value)
            ->where('processable_id', $taskId)
            ->with(['procedureSetting', 'takenByUser'])
            ->orderBy('taken_at')
            ->get();

        // Load all completed processes for this task, with steps and action users.
        $processes = Process::query()
            ->where('processable_type', $task->procedureSettingType()->value)
            ->where('processable_id', $taskId)
            ->where('status', ProcessStatus::Completed)
            ->with(['steps.procedureSettingStep', 'steps.actionByUser'])
            ->orderBy('created_at')
            ->get();

        // Collect all media IDs from process metadata to batch-load.
        $allMediaIds = [];
        foreach ($processes as $process) {
            $fileIds = $process->metadata['files'] ?? [];
            if (is_array($fileIds)) {
                foreach ($fileIds as $id) {
                    $allMediaIds[] = (int) $id;
                }
            }
        }

        $mediaMap = [];
        if ($allMediaIds !== []) {
            $mediaItems = CustomMedia::query()
                ->whereIn('id', array_unique($allMediaIds))
                ->get();
            $mediaMap = $mediaItems->keyBy('id')->all();
        }

        // Match each taken record to its process and build enriched items.
        $items = [];
        $usedProcessIds = [];
        foreach ($taken as $takenRecord) {
            $process = $this->matchProcess($takenRecord, $processes, $usedProcessIds);

            $attachments = [];
            $formData = null;
            if ($process) {
                $fileIds = $process->metadata['files'] ?? [];
                if (is_array($fileIds)) {
                    foreach ($fileIds as $id) {
                        if (isset($mediaMap[$id])) {
                            $attachments[] = $mediaMap[$id];
                        }
                    }
                }
                $formData = $process->metadata['update'] ?? $process->metadata ?? null;
            }

            // Fall back to InternalProcedureTaken.metadata when no Process matched
            // (e.g., takeAction path or auto-approve with no Process created).
            if ($formData === null && $takenRecord->metadata !== null) {
                $formData = $takenRecord->metadata['update'] ?? $takenRecord->metadata;
            }

            $items[] = [
                'taken' => $takenRecord,
                'process' => $process,
                'attachments' => $attachments,
                'form_data' => $formData,
            ];
        }

        return $this->buildResult($items, $taken);
    }

    /**
     * Match a taken record to its corresponding completed Process.
     * Matches by procedure_setting_id; for multiple processes with the same
     * setting, picks the one whose created_at is closest to (and before) taken_at.
     *
     * @param Collection<int, Process> $processes
     * @param list<string> $usedProcessIds Already-matched process IDs (to avoid duplicates)
     */
    private function matchProcess(
        InternalProcedureTaken $taken,
        Collection $processes,
        array &$usedProcessIds,
    ): ?Process {
        $candidates = $processes
            ->where('procedure_setting_id', $taken->procedure_setting_id)
            ->whereNotIn('id', $usedProcessIds);

        if ($candidates->isEmpty()) {
            return null;
        }

        // Pick the process created closest to (and before) taken_at.
        $best = null;
        $bestDiff = PHP_FLOAT_MAX;
        foreach ($candidates as $process) {
            $processCreated = $process->created_at?->getTimestamp() ?? 0;
            $takenAt = $taken->taken_at?->getTimestamp() ?? 0;
            $diff = abs($processCreated - $takenAt);
            if ($diff < $bestDiff) {
                $bestDiff = $diff;
                $best = $process;
            }
        }

        if ($best) {
            $usedProcessIds[] = $best->id;
        }

        return $best;
    }

    /**
     * @param list<array{taken: InternalProcedureTaken, process: ?Process, attachments: list<CustomMedia>, form_data: ?array}> $items
     * @param Collection<int, InternalProcedureTaken> $taken
     * @return array{items: list<array>, summary: array}
     */
    private function buildResult(array $items, Collection $taken): array
    {
        $total      = $taken->count();
        $last       = $taken->last();
        $first      = $taken->first();

        $avgProgress = $total > 0
            ? (int) round(
                $taken->avg(fn ($t) => (float) ($t->procedureSetting?->percentage ?? 0))
            )
            : 0;

        $summary = [
            'total'       => $total,
            'last_action' => $last?->procedureSetting?->name,
            'start_date'  => $first?->taken_at?->format('Y-m-d'),
            'progress'    => $avgProgress,
        ];

        return [
            'items'   => $items,
            'summary' => $summary,
        ];
    }
}
