<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Services\TaskLocationPunchResolver;
use Modules\User\Models\User;
use PHPUnit\Framework\TestCase;

/**
 * Geofence validation only answers "inside any allowed circle", so the task a punch was
 * taken at has to be resolved while the row is written (INV-20). An office wins over an
 * overlapping task geofence — an employee standing at their own branch is at work.
 */
class TaskLocationPunchResolverTest extends TestCase
{
    private const OFFICE = ['name' => 'HQ', 'latitude' => 24.7136, 'longitude' => 46.6753, 'radius' => 150];

    private const TASK_SITE = [
        'name'         => 'صيانة محول',
        'latitude'     => 24.8000,
        'longitude'    => 46.8000,
        'radius'       => 100,
        'source'       => 'employee_task',
        'reference_id' => 'task-1',
    ];

    public function test_punch_inside_a_task_geofence_returns_the_task(): void
    {
        $taskId = $this->resolve(
            ['latitude' => 24.8000, 'longitude' => 46.8000],
            [self::OFFICE],
            [self::TASK_SITE]
        );

        $this->assertSame('task-1', $taskId);
    }

    public function test_punch_at_the_office_is_not_a_task_punch(): void
    {
        $taskId = $this->resolve(
            ['latitude' => 24.7136, 'longitude' => 46.6753],
            [self::OFFICE],
            [self::TASK_SITE]
        );

        $this->assertNull($taskId);
    }

    /**
     * A task geofence drawn over the branch must not turn an ordinary office day into a
     * task day; the constraint location is checked first.
     */
    public function test_overlapping_geofences_resolve_to_the_office(): void
    {
        $taskOverOffice = self::TASK_SITE;
        $taskOverOffice['latitude']  = 24.7136;
        $taskOverOffice['longitude'] = 46.6753;

        $taskId = $this->resolve(
            ['latitude' => 24.7136, 'longitude' => 46.6753],
            [self::OFFICE],
            [$taskOverOffice]
        );

        $this->assertNull($taskId);
    }

    public function test_punch_outside_everything_is_not_a_task_punch(): void
    {
        $taskId = $this->resolve(
            ['latitude' => 21.4858, 'longitude' => 39.1925],
            [self::OFFICE],
            [self::TASK_SITE]
        );

        $this->assertNull($taskId);
    }

    public function test_missing_coordinates_are_not_a_task_punch(): void
    {
        $this->assertNull($this->resolve(null, [self::OFFICE], [self::TASK_SITE]));
        $this->assertNull($this->resolve(['address' => 'somewhere'], [self::OFFICE], [self::TASK_SITE]));
    }

    /**
     * With no active task there is nothing a punch could be attributed to, wherever it
     * was taken.
     */
    public function test_no_active_task_is_never_a_task_punch(): void
    {
        $taskId = $this->resolve(
            ['latitude' => 24.8000, 'longitude' => 46.8000],
            [self::OFFICE],
            []
        );

        $this->assertNull($taskId);
    }

    /**
     * A punch must never be lost because location lookup failed; it stays ordinary
     * attendance instead.
     */
    public function test_a_failing_constraint_lookup_falls_back_to_no_task(): void
    {
        $constraintService = $this->createMock(AttendanceConstraintService::class);
        $constraintService->method('clockInLocationsByKindForUser')
            ->willThrowException(new \RuntimeException('no tenant'));

        $resolver = new TaskLocationPunchResolver($constraintService);

        $this->assertNull($resolver->taskIdFor(new User(), ['latitude' => 24.8, 'longitude' => 46.8]));
    }

    /**
     * @param  array<string, mixed>|null  $coordinates
     * @param  list<array<string, mixed>>  $constraintLocations
     * @param  list<array<string, mixed>>  $taskLocations
     */
    private function resolve(?array $coordinates, array $constraintLocations, array $taskLocations): ?string
    {
        $constraintService = $this->createMock(AttendanceConstraintService::class);
        $constraintService->method('clockInLocationsByKindForUser')
            ->willReturn(['constraint' => $constraintLocations, 'task' => $taskLocations]);

        return (new TaskLocationPunchResolver($constraintService))->taskIdFor(new User(), $coordinates);
    }
}
