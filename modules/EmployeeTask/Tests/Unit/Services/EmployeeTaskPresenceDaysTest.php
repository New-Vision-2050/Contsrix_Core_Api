<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Tests\Unit\Services;

use Carbon\CarbonImmutable;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Services\EmployeeTaskPresenceService;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class EmployeeTaskPresenceDaysTest extends TestCase
{
    private EmployeeTaskPresenceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new EmployeeTaskPresenceService();
    }

    public function test_session_left_open_is_capped_instead_of_running_until_now(): void
    {
        $end = $this->sessionEnd(
            start: '2026-06-12 09:00:00',
            end: null,
            taskEnd: null,
            now: '2026-08-24 12:00:00',
        );

        $this->assertSame('2026-06-13 09:00:00', $end->format('Y-m-d H:i:s'));
    }

    public function test_open_session_ends_at_the_task_end_when_it_comes_first(): void
    {
        $end = $this->sessionEnd(
            start: '2026-06-12 09:00:00',
            end: null,
            taskEnd: '2026-06-12 17:30:00',
            now: '2026-08-24 12:00:00',
        );

        $this->assertSame('2026-06-12 17:30:00', $end->format('Y-m-d H:i:s'));
    }

    public function test_running_session_ends_at_now(): void
    {
        $end = $this->sessionEnd(
            start: '2026-08-24 09:00:00',
            end: null,
            taskEnd: null,
            now: '2026-08-24 12:00:00',
        );

        $this->assertSame('2026-08-24 12:00:00', $end->format('Y-m-d H:i:s'));
    }

    public function test_closed_session_keeps_its_own_end(): void
    {
        $end = $this->sessionEnd(
            start: '2026-08-24 09:00:00',
            end: '2026-08-24 15:00:00',
            taskEnd: null,
            now: '2026-08-24 18:00:00',
        );

        $this->assertSame('2026-08-24 15:00:00', $end->format('Y-m-d H:i:s'));
    }

    public function test_in_progress_task_marks_its_own_day_and_today_only(): void
    {
        $task = new EmployeeTaskRequest();
        $task->setAttribute('status', EmployeeTaskStatus::InProgress->value);
        $task->setAttribute('task_date', '2026-07-08');

        $days = $this->activeDays($task, null, '2026-08-24');

        $this->assertSame(['2026-07-08', '2026-08-24'], $days);
    }

    public function test_in_progress_notification_marks_its_own_day_and_today_only(): void
    {
        $task = new EmployeeTaskRequest();
        $task->setAttribute('status', EmployeeTaskStatus::Approved->value);
        $task->setAttribute('task_date', '2026-07-08');

        $notification = new ProjectNotification();
        $notification->setAttribute('status', 'in_progress');
        $notification->setAttribute('task_date', '2026-07-08');

        $days = $this->activeDays($task, $notification, '2026-08-24');

        $this->assertSame(['2026-07-08', '2026-08-24'], $days);
    }

    public function test_completed_notification_does_not_reach_today(): void
    {
        $task = new EmployeeTaskRequest();
        $task->setAttribute('status', EmployeeTaskStatus::Completed->value);
        $task->setAttribute('task_date', '2026-07-08');

        $notification = new ProjectNotification();
        $notification->setAttribute('status', 'completed');
        $notification->setAttribute('task_date', '2026-07-08');

        $days = $this->activeDays($task, $notification, '2026-08-24');

        $this->assertSame(['2026-07-08'], $days);
    }

    public function test_task_that_is_not_active_marks_no_day(): void
    {
        $task = new EmployeeTaskRequest();
        $task->setAttribute('status', EmployeeTaskStatus::Completed->value);
        $task->setAttribute('task_date', '2026-07-08');

        $this->assertSame([], $this->activeDays($task, null, '2026-08-24'));
    }

    private function sessionEnd(string $start, ?string $end, ?string $taskEnd, string $now): CarbonImmutable
    {
        $method = new ReflectionMethod($this->service, 'resolveSessionEnd');
        $method->setAccessible(true);

        return $method->invoke(
            $this->service,
            CarbonImmutable::parse($start),
            $end !== null ? CarbonImmutable::parse($end) : null,
            $taskEnd !== null ? CarbonImmutable::parse($taskEnd) : null,
            CarbonImmutable::parse($now),
        );
    }

    /**
     * @return list<string>
     */
    private function activeDays(?EmployeeTaskRequest $task, ?ProjectNotification $notification, string $today): array
    {
        $method = new ReflectionMethod($this->service, 'resolveActiveDays');
        $method->setAccessible(true);

        return $method->invoke($this->service, $task, $notification, $today);
    }
}
