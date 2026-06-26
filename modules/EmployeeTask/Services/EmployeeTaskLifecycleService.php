<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Modules\EmployeeTask\DTO\EndTaskDTO;
use Modules\EmployeeTask\DTO\StartTaskDTO;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Jobs\AutoCloseTaskAtDurationExpiryJob;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;
use Modules\EmployeeTask\Repositories\EmployeeTaskSessionRepository;
use Modules\EmployeeTask\Services\EmployeeTaskApprovalService;
use Modules\EmployeeTask\Services\EmployeeTaskEndRequestService;
use Modules\ProcedureSetting\Events\WorkflowProcedureTaken;
use Modules\User\Models\User;

final class EmployeeTaskLifecycleService
{
    public function __construct(
        private readonly EmployeeTaskRepository           $taskRepo,
        private readonly EmployeeTaskSessionRepository    $sessionRepo,
        private readonly EmployeeTaskLocationService      $locationService,
        private readonly EmployeeTaskApprovalService      $approvalService,
        private readonly EmployeeTaskEndRequestService    $endRequestService,
        private readonly EmployeeTaskStartRequestService   $startRequestService,
        private readonly EmployeeTaskFormConditionService $conditionService,
    ) {}

    public function start(string $taskId, StartTaskDTO $dto, User $user): EmployeeTaskRequest
    {
        $task = $this->taskRepo->findById($taskId);

        if (!$task) {
            throw EmployeeTaskException::notFound();
        }

        if ($task->status !== EmployeeTaskStatus::Approved->value) {
            throw EmployeeTaskException::notApproved();
        }

        if ($task->hasPendingStartRequest()) {
            throw EmployeeTaskException::pendingStartRequestExists();
        }

        $activeTask = $this->taskRepo->findActiveTaskForUser((string) $user->id);
        if ($activeTask && $activeTask->id !== $task->id) {
            throw EmployeeTaskException::hasOtherOpenTask();
        }

        $this->conditionService->checkStartTaskConditions($task, $user, $dto->latitude, $dto->longitude);

        $procedureSetting = $this->startRequestService->resolveStartTaskProcedure(
            $task,
            $dto->internalProcedureSettingId,
        );

        if ($procedureSetting !== null) {
            $this->startRequestService->create($task, $dto, $procedureSetting);
            return $task->fresh()->load(['sessions']);
        }

        return $this->performStart($task, $dto, $user);
    }

    /**
     * Execute the start-task business logic immediately (no procedure involved).
     * Also called internally when a start request is auto-approved.
     */
    public function performStart(EmployeeTaskRequest $task, StartTaskDTO $dto, User $user): EmployeeTaskRequest
    {
        $timezone      = $this->resolveTimezone($user);
        $radiusMeters  = $this->locationService->snapshotRadiusFromConstraint($user);
        $now           = CarbonImmutable::now($timezone);

        $this->taskRepo->update($task, [
            'status'         => EmployeeTaskStatus::InProgress->value,
            'time_from'      => $now->format('Y-m-d H:i:s'),
            'radius_meters'  => $radiusMeters,
            'timezone'       => $timezone,
            'start_location' => ['latitude' => $dto->latitude, 'longitude' => $dto->longitude],
        ]);

        $this->sessionRepo->create([
            'employee_task_request_id' => $task->id,
            'company_id'               => $task->company_id,
            'start_time'               => $now->format('Y-m-d H:i:s'),
            'start_latitude'           => $dto->latitude,
            'start_longitude'          => $dto->longitude,
            'source'                   => 'manual',
        ]);

        $task->refresh();

        $maxOverTimeHours = $this->resolveMaxOverTimeHours($user);
        $deadline = $now
            ->addHours((float) $task->duration_hours)
            ->addHours($maxOverTimeHours);

        $closeAtIso = $now->addHours((float) $task->duration_hours)->toIso8601String();

        AutoCloseTaskAtDurationExpiryJob::dispatch(
            taskId:     $task->id,
            companyId:  $task->company_id,
            closeAtIso: $closeAtIso,
        )->delay($deadline);

        return $task->load(['sessions']);
    }

    public function pause(string $taskId, string $userId): EmployeeTaskRequest
    {
        $task = $this->taskRepo->findById($taskId);

        if (!$task) {
            throw EmployeeTaskException::notFound();
        }

        if ($task->status !== EmployeeTaskStatus::InProgress->value) {
            throw EmployeeTaskException::notInProgress();
        }

        $timezone = $task->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';
        $now      = CarbonImmutable::now($timezone);

        $activeSession = $this->sessionRepo->findActiveByTask($taskId);

        if ($activeSession) {
            $sessionStart    = CarbonImmutable::parse($activeSession->start_time, $timezone);
            $durationMinutes = max(0, (int) $sessionStart->diffInMinutes($now));

            $this->sessionRepo->closeSession($activeSession, [
                'end_time'         => $now->format('Y-m-d H:i:s'),
                'duration_minutes' => $durationMinutes,
                'source'           => 'manual',
            ]);
        }

        $this->taskRepo->update($task, ['status' => EmployeeTaskStatus::Paused->value]);

        return $task->refresh()->load(['sessions']);
    }

    public function resume(string $taskId, float $latitude, float $longitude): EmployeeTaskRequest
    {
        $task = $this->taskRepo->findById($taskId);

        if (!$task) {
            throw EmployeeTaskException::notFound();
        }

        if ($task->status !== EmployeeTaskStatus::Paused->value) {
            throw EmployeeTaskException::notPaused();
        }

        $timezone = $task->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';
        $now      = CarbonImmutable::now($timezone);

        $this->sessionRepo->create([
            'employee_task_request_id' => $task->id,
            'company_id'               => $task->company_id,
            'start_time'               => $now->format('Y-m-d H:i:s'),
            'start_latitude'           => $latitude,
            'start_longitude'          => $longitude,
            'source'                   => 'manual',
        ]);

        $this->taskRepo->update($task, ['status' => EmployeeTaskStatus::InProgress->value]);

        return $task->refresh()->load(['sessions']);
    }

    public function end(string $taskId, EndTaskDTO $dto): EmployeeTaskRequest
    {
        $task = $this->taskRepo->findById($taskId);

        if (!$task) {
            throw EmployeeTaskException::notFound();
        }

        $validStatuses = [EmployeeTaskStatus::InProgress->value, EmployeeTaskStatus::Paused->value];
        if (!in_array($task->status, $validStatuses, true)) {
            throw EmployeeTaskException::invalidStatus($task->status, ...$validStatuses);
        }

        if ($task->hasPendingEndRequest()) {
            throw EmployeeTaskException::pendingEndRequestExists();
        }

        $this->conditionService->checkEndTaskConditions($task, $dto->latitude, $dto->longitude);

        $procedureSetting = $this->endRequestService->resolveEndTaskProcedure(
            $task,
            $dto->internalProcedureSettingId,
        );

        if ($procedureSetting !== null) {
            $this->endRequestService->create($task, $dto, $procedureSetting);
            return $task->fresh()->load(['sessions']);
        }

        $task = $this->performEnd($task, $dto);

        if ($dto->internalProcedureSettingId) {
            Event::dispatch(new WorkflowProcedureTaken(
                'employee_task',
                $task->id,
                $dto->internalProcedureSettingId,
            ));
        }

        return $task;
    }

    /**
     * Execute the end-task business logic immediately (no procedure involved).
     * Also called internally when an end request is auto-approved.
     */
    public function performEnd(EmployeeTaskRequest $task, EndTaskDTO $dto): EmployeeTaskRequest
    {
        $timezone = $task->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';
        $now      = CarbonImmutable::now($timezone);

        $activeSession = $this->sessionRepo->findActiveByTask($task->id);

        if ($activeSession) {
            $sessionStart    = CarbonImmutable::parse($activeSession->start_time, $timezone);
            $durationMinutes = max(0, (int) $sessionStart->diffInMinutes($now));

            $this->sessionRepo->closeSession($activeSession, [
                'end_time'         => $now->format('Y-m-d H:i:s'),
                'duration_minutes' => $durationMinutes,
                'end_latitude'     => $dto->latitude,
                'end_longitude'    => $dto->longitude,
                'source'           => 'manual',
            ]);
        }

        $totalSessionMinutes = $this->sessionRepo->sumCompletedMinutes($task->id);
        $timeFrom            = CarbonImmutable::parse($task->time_from, $timezone);
        $totalElapsedMinutes = max(0, (int) $timeFrom->diffInMinutes($now));
        $totalPauseMinutes   = max(0, $totalElapsedMinutes - $totalSessionMinutes);
        $totalTaskHours      = round($totalSessionMinutes / 60, 2);

        $this->taskRepo->update($task, [
            'status'              => EmployeeTaskStatus::Completed->value,
            'time_to'             => $now->format('Y-m-d H:i:s'),
            'total_task_hours'    => $totalTaskHours,
            'total_pause_minutes' => $totalPauseMinutes,
            'shift_end_method'    => 'manual',
            'end_location'        => ['latitude' => $dto->latitude, 'longitude' => $dto->longitude],
            'notes'               => $dto->notes ?? $task->notes,
        ]);

        $task->refresh()->load(['sessions']);
        return $task;
    }

    private function resolveTimezone(User $user): string
    {
        $timezones = $user->userProfessionalData?->branch?->address?->country?->timezones;
        if (is_array($timezones) && isset($timezones[0]['zoneName'])) {
            return $timezones[0]['zoneName'];
        }
        return getTimeZoneBranchByRequest() ?? config('app.timezone') ?? 'Asia/Riyadh';
    }

    private function resolveMaxOverTimeHours(User $user): float
    {
        $constraint = $user->userProfessionalData?->attendanceConstraint;

        return (float) ($constraint?->max_over_time ?? 0.0);
    }
}
