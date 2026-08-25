<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Presenters;

use Modules\Attendance\Presenters\AttendanceCalendarPresenter;
use PHPUnit\Framework\TestCase;

class AttendanceCalendarPresenterTasksTest extends TestCase
{
    public function test_on_task_day_exposes_the_task_that_caused_the_status(): void
    {
        $presenter = new AttendanceCalendarPresenter([
            'days' => [
                [
                    'date'             => '2026-08-24',
                    'day_name'         => 'الاثنين',
                    'day_number'       => 24,
                    'status_key'       => 'on_task',
                    'status'           => 'متواجد',
                    'work_hours'       => null,
                    'attendance_count' => 2,
                    'tasks'            => [
                        [
                            'id'                  => 'task-1',
                            'title'               => 'صيانة محول',
                            'status'              => 'in_progress',
                            'source'              => 'project_notification',
                            'task_date'           => '2026-08-24',
                            'project_id'          => 'project-1',
                            'notification_id'     => 'notification-1',
                            'notification_number' => 'PN-1024',
                            'notification_status' => 'in_progress',
                            'minutes'             => 95,
                            'hours'               => 1.58,
                        ],
                    ],
                ],
            ],
            'summary' => [],
        ]);

        $day = $presenter->present()['days'][0];

        $this->assertSame('on_task', $day['status_key']);
        $this->assertCount(1, $day['tasks']);

        $task = $day['tasks'][0];

        $this->assertSame('task-1', $task['id']);
        $this->assertSame('صيانة محول', $task['title']);
        $this->assertSame('project_notification', $task['source']);
        $this->assertSame('PN-1024', $task['notification_number']);
        $this->assertSame(95, $task['work_minutes']);
        $this->assertSame('01h 35m', $task['duration_formatted']);
    }

    public function test_days_without_tasks_expose_an_empty_list(): void
    {
        $presenter = new AttendanceCalendarPresenter([
            'days' => [
                [
                    'date'       => '2026-08-21',
                    'status_key' => 'off',
                    'status'     => 'عطلة',
                ],
            ],
            'summary' => [],
        ]);

        $this->assertSame([], $presenter->present()['days'][0]['tasks']);
    }
}
