<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Repositories;

use Modules\EmployeeTask\Models\EmployeeTaskSession;

class EmployeeTaskSessionRepository
{
    public function create(array $data): EmployeeTaskSession
    {
        return EmployeeTaskSession::query()->create($data);
    }

    public function closeSession(EmployeeTaskSession $session, array $data): EmployeeTaskSession
    {
        $session->update($data);
        return $session->fresh();
    }

    public function findActiveByTask(string $taskId): ?EmployeeTaskSession
    {
        return EmployeeTaskSession::query()
            ->where('employee_task_request_id', $taskId)
            ->whereNull('end_time')
            ->first();
    }

    public function sumCompletedMinutes(string $taskId): int
    {
        return (int) EmployeeTaskSession::query()
            ->where('employee_task_request_id', $taskId)
            ->whereNotNull('end_time')
            ->sum('duration_minutes');
    }
}
