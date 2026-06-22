<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;
use Modules\ProcedureSetting\Models\InternalProcedureTaken;

final class EmployeeTaskProceduresService
{
    public function __construct(
        private readonly EmployeeTaskRepository $repository,
    ) {}

    /**
     * Return all taken procedures for a task, ordered by taken_at ascending,
     * together with a summary (total, last action name, start date, progress %).
     *
     * @return array{items: list<InternalProcedureTaken>, summary: array}
     */
    public function forTask(string $taskId): array
    {
        $task = $this->repository->findById($taskId);

        if (! $task) {
            throw EmployeeTaskException::notFound();
        }

        /** @var Collection<int, InternalProcedureTaken> $taken */
        $taken = InternalProcedureTaken::query()
            ->where('processable_type', 'employee_task')
            ->where('processable_id', $taskId)
            ->with(['procedureSetting', 'takenByUser'])
            ->orderBy('taken_at')
            ->get();

        $total      = $taken->count();
        $last       = $taken->last();
        $first      = $taken->first();

        $avgProgress = $total > 0
            ? (int) round(
                $taken->avg(fn ($t) => (float) ($t->procedureSetting?->percentage ?? 0))
            )
            : 0;

        $summary = [
            'total'      => $total,
            'last_action' => $last?->procedureSetting?->name,
            'start_date'  => $first?->taken_at?->format('Y-m-d'),
            'progress'    => $avgProgress,
        ];

        return [
            'items'   => $taken,
            'summary' => $summary,
        ];
    }
}
