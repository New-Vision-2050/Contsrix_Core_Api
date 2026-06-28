<?php

declare(strict_types=1);

namespace Tests\Unit\EmployeeTask;

use Mockery;
use Modules\EmployeeTask\DTO\StartTaskDTO;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Models\EmployeeTaskStartRequest;
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;
use Modules\EmployeeTask\Repositories\EmployeeTaskSessionRepository;
use Modules\EmployeeTask\Services\EmployeeTaskApprovalService;
use Modules\EmployeeTask\Services\EmployeeTaskEndRequestService;
use Modules\EmployeeTask\Services\EmployeeTaskFormConditionService;
use Modules\EmployeeTask\Services\EmployeeTaskLifecycleService;
use Modules\EmployeeTask\Services\EmployeeTaskLocationService;
use Modules\EmployeeTask\Services\EmployeeTaskRequestService;
use Modules\EmployeeTask\Services\EmployeeTaskStartRequestService;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\Process\Models\Process;
use Modules\User\Models\User;
use Tests\TestCase;

class EmployeeTaskLifecycleServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_start_skips_condition_check_for_project_notification_tasks(): void
    {
        $task = $this->makeProjectNotificationTask();
        $user = $this->makeUser();

        $conditionService = Mockery::mock(EmployeeTaskFormConditionService::class);
        $conditionService->shouldNotReceive('checkStartTaskConditions');

        $startRequestService = Mockery::mock(EmployeeTaskStartRequestService::class);
        $startRequestService->shouldReceive('resolveStartTaskProcedure')
            ->once()
            ->andReturn(Mockery::mock(ProcedureSetting::class));
        $startRequestService->shouldReceive('createFromProcess')
            ->once()
            ->andReturn(Mockery::mock(EmployeeTaskStartRequest::class));

        $requestService = Mockery::mock(EmployeeTaskRequestService::class);
        $requestService->shouldReceive('createLifecycleProcess')
            ->once()
            ->andReturn(Mockery::mock(Process::class));

        $service = $this->createService(
            taskRepo: $this->taskRepoReturning($task),
            startRequestService: $startRequestService,
            conditionService: $conditionService,
            requestService: $requestService,
        );

        $dto = new StartTaskDTO(
            latitude: 24.7,
            longitude: 46.7,
            internalProcedureSettingId: null,
            notes: null,
        );

        $service->start('task-id', $dto, $user);
    }

    public function test_start_evaluates_condition_check_for_regular_employee_tasks(): void
    {
        $task = $this->makeRegularTask();
        $user = $this->makeUser();

        $conditionService = Mockery::mock(EmployeeTaskFormConditionService::class);
        $conditionService->shouldReceive('checkStartTaskConditions')
            ->once()
            ->with($task, $user, 24.7, 46.7);

        $startRequestService = Mockery::mock(EmployeeTaskStartRequestService::class);
        $startRequestService->shouldReceive('resolveStartTaskProcedure')
            ->once()
            ->andReturn(Mockery::mock(ProcedureSetting::class));
        $startRequestService->shouldReceive('createFromProcess')
            ->once()
            ->andReturn(Mockery::mock(EmployeeTaskStartRequest::class));

        $requestService = Mockery::mock(EmployeeTaskRequestService::class);
        $requestService->shouldReceive('createLifecycleProcess')
            ->once()
            ->andReturn(Mockery::mock(Process::class));

        $service = $this->createService(
            taskRepo: $this->taskRepoReturning($task),
            startRequestService: $startRequestService,
            conditionService: $conditionService,
            requestService: $requestService,
        );

        $dto = new StartTaskDTO(
            latitude: 24.7,
            longitude: 46.7,
            internalProcedureSettingId: null,
            notes: null,
        );

        $service->start('task-id', $dto, $user);
    }

    private function makeProjectNotificationTask(): EmployeeTaskRequest
    {
        $task = Mockery::mock(EmployeeTaskRequest::class)->makePartial();
        $task->id = 'task-id';
        $task->status = EmployeeTaskStatus::Approved->value;
        $task->is_project_notification = true;
        $task->user_id = 'user-id';
        $task->company_id = 'company-id';
        $task->duration_hours = 2;
        $task->shouldReceive('hasPendingStartRequest')->andReturn(false);

        return $task;
    }

    private function makeRegularTask(): EmployeeTaskRequest
    {
        $task = Mockery::mock(EmployeeTaskRequest::class)->makePartial();
        $task->id = 'task-id';
        $task->status = EmployeeTaskStatus::Approved->value;
        $task->is_project_notification = false;
        $task->user_id = 'user-id';
        $task->company_id = 'company-id';
        $task->duration_hours = 2;
        $task->shouldReceive('hasPendingStartRequest')->andReturn(false);

        return $task;
    }

    private function makeUser(): User
    {
        $user = new User();
        $user->id = '550e8400-e29b-41d4-a716-446655440000';

        return $user;
    }

    private function taskRepoReturning(EmployeeTaskRequest $task): EmployeeTaskRepository
    {
        $repo = Mockery::mock(EmployeeTaskRepository::class);
        $repo->shouldReceive('findById')->with('task-id')->andReturn($task);
        $repo->shouldReceive('findActiveTaskForUser')->with('550e8400-e29b-41d4-a716-446655440000')->andReturnNull();

        return $repo;
    }

    private function createService(
        EmployeeTaskRepository $taskRepo,
        EmployeeTaskStartRequestService $startRequestService,
        EmployeeTaskFormConditionService $conditionService,
        EmployeeTaskRequestService $requestService,
    ): EmployeeTaskLifecycleService {
        return new EmployeeTaskLifecycleService(
            $taskRepo,
            Mockery::mock(EmployeeTaskSessionRepository::class),
            Mockery::mock(EmployeeTaskLocationService::class),
            Mockery::mock(EmployeeTaskApprovalService::class),
            Mockery::mock(EmployeeTaskEndRequestService::class),
            $startRequestService,
            $conditionService,
            $requestService,
        );
    }
}
