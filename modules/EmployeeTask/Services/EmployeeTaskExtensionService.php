<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\EmployeeTask\DTO\CreateExtensionRequestDTO;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Jobs\AutoCloseTaskAtDurationExpiryJob;
use Modules\EmployeeTask\Models\EmployeeTaskExtensionRequest;
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;

final class EmployeeTaskExtensionService
{
    public function __construct(
        private readonly EmployeeTaskRepository $taskRepo,
    ) {}

    public function requestExtension(CreateExtensionRequestDTO $dto): EmployeeTaskExtensionRequest
    {
        $task = $this->taskRepo->findById($dto->taskId);

        if (!$task) {
            throw EmployeeTaskException::notFound();
        }

        $allowedStatuses = [EmployeeTaskStatus::InProgress->value, EmployeeTaskStatus::Paused->value];
        if (!in_array($task->status, $allowedStatuses, true)) {
            throw EmployeeTaskException::extensionNotAllowed();
        }

        if ($task->hasPendingExtension()) {
            throw EmployeeTaskException::pendingExtensionExists();
        }

        return DB::transaction(function () use ($task, $dto): EmployeeTaskExtensionRequest {
            $extension = EmployeeTaskExtensionRequest::query()->create(
                array_merge($dto->toArray(), ['company_id' => $task->company_id])
            );

            $task->update(['last_extension_status' => 'extension_pending']);

            return $extension;
        });
    }

    public function approveExtension(string $extensionId, string $adminId): EmployeeTaskExtensionRequest
    {
        $extension = EmployeeTaskExtensionRequest::query()->findOrFail($extensionId);
        $task      = $this->taskRepo->findById($extension->employee_task_request_id);

        if (!$task) {
            throw EmployeeTaskException::notFound();
        }

        return DB::transaction(function () use ($extension, $task, $adminId): EmployeeTaskExtensionRequest {
            if ($task->original_duration_hours === null) {
                $task->update(['original_duration_hours' => $task->duration_hours]);
            }

            $newDuration = (float) $task->duration_hours + (float) $extension->additional_hours;
            $task->update([
                'duration_hours'       => $newDuration,
                'last_extension_status' => 'extension_approved',
            ]);

            $extension->update([
                'status'      => 'approved',
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
            ]);

            if ($task->time_from) {
                $timezone  = $task->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';
                $timeFrom  = CarbonImmutable::parse($task->time_from, $timezone);
                $closeAtIso = $timeFrom->addHours($newDuration)->toIso8601String();
                $deadline  = $timeFrom->addHours($newDuration);

                AutoCloseTaskAtDurationExpiryJob::dispatch(
                    taskId:     $task->id,
                    companyId:  $task->company_id,
                    closeAtIso: $closeAtIso,
                )->delay($deadline);
            }

            return $extension->fresh();
        });
    }

    public function rejectExtension(string $extensionId, string $adminId, ?string $notes): EmployeeTaskExtensionRequest
    {
        $extension = EmployeeTaskExtensionRequest::query()->findOrFail($extensionId);
        $task      = $this->taskRepo->findById($extension->employee_task_request_id);

        if (!$task) {
            throw EmployeeTaskException::notFound();
        }

        return DB::transaction(function () use ($extension, $task, $adminId, $notes): EmployeeTaskExtensionRequest {
            $extension->update([
                'status'       => 'rejected',
                'reviewed_by'  => $adminId,
                'reviewed_at'  => now(),
                'review_notes' => $notes,
            ]);

            $task->update(['last_extension_status' => 'extension_rejected']);

            return $extension->fresh();
        });
    }

    public function listExtensions(string $taskId): Collection
    {
        return EmployeeTaskExtensionRequest::query()
            ->where('employee_task_request_id', $taskId)
            ->orderByDesc('created_at')
            ->get();
    }
}
