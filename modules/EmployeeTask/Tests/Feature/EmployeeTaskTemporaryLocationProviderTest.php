<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Company\CompanyCore\Models\Company;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Services\EmployeeTaskTemporaryLocationProvider;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Feature tests for EmployeeTaskTemporaryLocationProvider (Attendance Rules V2,
 * Feature 6 §10.2).
 *
 * Invariants under test:
 *  - Only in_progress tasks with time_from + coordinates emit a location.
 *  - A stuck in_progress task past time_from + duration_hours emits NOTHING
 *    (status alone is never trusted — jobs can be lost).
 *  - expires_at is time_from + duration_hours parsed in the task's frozen
 *    timezone (branch-TZ wall-clock string, tz passed as 2nd parse argument).
 *
 * @group requires-db
 */
final class EmployeeTaskTemporaryLocationProviderTest extends TestCase
{
    use DatabaseTransactions;

    private const TZ = 'Asia/Riyadh';

    private EmployeeTaskTemporaryLocationProvider $provider;
    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company  = Company::factory()->create(['status' => 'active']);
        $this->user     = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);

        $this->provider = $this->app->make(EmployeeTaskTemporaryLocationProvider::class);
    }

    public function test_in_progress_task_within_duration_emits_one_location(): void
    {
        $timeFrom = CarbonImmutable::now(self::TZ)->subMinutes(30);

        $task = $this->createTask([
            'status'         => EmployeeTaskStatus::InProgress->value,
            'time_from'      => $timeFrom->format('Y-m-d H:i:s'),
            'timezone'       => self::TZ,
            'duration_hours' => 2,
            'radius_meters'  => 150,
            'title'          => 'Fix AC unit',
        ]);

        $locations = $this->provider->temporaryLocationsFor($this->user, CarbonImmutable::now(self::TZ));

        $this->assertCount(1, $locations);

        $location = $locations[0];

        $expectedExpiry = CarbonImmutable::parse($timeFrom->format('Y-m-d H:i:s'), self::TZ)
            ->addMinutes(120);

        $this->assertSame('task:'.$task->id, $location['id']);
        $this->assertSame('Fix AC unit', $location['name']);
        $this->assertEqualsWithDelta(24.7136, $location['latitude'],  0.0000001);
        $this->assertEqualsWithDelta(46.6753, $location['longitude'], 0.0000001);
        $this->assertSame(150, $location['radius']);
        $this->assertSame('employee_task', $location['source']);
        $this->assertSame($expectedExpiry->toIso8601String(), $location['expires_at']);
        $this->assertSame((string) $task->id, $location['reference_id']);
    }

    public function test_stuck_in_progress_task_past_duration_emits_no_location(): void
    {
        // Status still says in_progress (lost auto-close job) but
        // time_from + duration_hours is already in the past.
        $this->createTask([
            'status'         => EmployeeTaskStatus::InProgress->value,
            'time_from'      => CarbonImmutable::now(self::TZ)->subHours(3)->format('Y-m-d H:i:s'),
            'timezone'       => self::TZ,
            'duration_hours' => 2,
        ]);

        $locations = $this->provider->temporaryLocationsFor($this->user, CarbonImmutable::now(self::TZ));

        $this->assertSame([], $locations);
    }

    public function test_approved_not_started_task_emits_no_location(): void
    {
        $this->createTask([
            'status'    => EmployeeTaskStatus::Approved->value,
            'time_from' => null,
            'timezone'  => null,
        ]);

        $locations = $this->provider->temporaryLocationsFor($this->user, CarbonImmutable::now(self::TZ));

        $this->assertSame([], $locations);
    }

    public function test_task_without_coordinates_emits_no_location(): void
    {
        $this->createTask([
            'status'         => EmployeeTaskStatus::InProgress->value,
            'time_from'      => CarbonImmutable::now(self::TZ)->subMinutes(30)->format('Y-m-d H:i:s'),
            'timezone'       => self::TZ,
            'duration_hours' => 2,
            'task_latitude'  => null,
            'task_longitude' => null,
        ]);

        $locations = $this->provider->temporaryLocationsFor($this->user, CarbonImmutable::now(self::TZ));

        $this->assertSame([], $locations);
    }

    public function test_other_users_task_emits_no_location(): void
    {
        $otherUser = User::factory()->create(['company_id' => $this->company->id]);

        $this->createTask([
            'user_id'        => $otherUser->id,
            'status'         => EmployeeTaskStatus::InProgress->value,
            'time_from'      => CarbonImmutable::now(self::TZ)->subMinutes(30)->format('Y-m-d H:i:s'),
            'timezone'       => self::TZ,
            'duration_hours' => 2,
        ]);

        $locations = $this->provider->temporaryLocationsFor($this->user, CarbonImmutable::now(self::TZ));

        $this->assertSame([], $locations);
    }

    /**
     * A project-notification task belongs to every user in the notification's
     * assigned_user_ids, not only the task's own user_id. Without this the assignee saw the
     * task on their calendar yet had no geofence to clock in at — the exact case INV-19's
     * removal of the on_task overlay depends on.
     */
    public function test_project_notification_assignee_emits_a_location(): void
    {
        $assignee = User::factory()->create(['company_id' => $this->company->id]);

        // The task's own user_id is someone else; the assignee only appears on the
        // notification. Project-notification task rows sit outside the tenant scope.
        $task = $this->createTask([
            'company_id'              => null,
            'user_id'                 => $this->user->id,
            'is_project_notification' => true,
            'status'                  => EmployeeTaskStatus::InProgress->value,
            'time_from'               => CarbonImmutable::now(self::TZ)->subMinutes(30)->format('Y-m-d H:i:s'),
            'timezone'                => self::TZ,
            'duration_hours'          => 2,
        ]);

        \Modules\Project\ProjectManagement\Models\ProjectNotification::create([
            'company_id'               => $this->company->id,
            'employee_task_request_id' => $task->id,
            'notification_number'      => 'PN-' . uniqid(),
            'status'                   => 'in_progress',
            'assigned_user_ids'        => [(string) $assignee->id],
        ]);

        $locations = $this->provider->temporaryLocationsFor($assignee, CarbonImmutable::now(self::TZ));

        $this->assertCount(1, $locations);
        $this->assertSame((string) $task->id, $locations[0]['reference_id']);
    }

    /**
     * The assignee path must not leak across to a user who is on neither the task nor the
     * notification.
     */
    public function test_user_not_on_the_notification_emits_no_location(): void
    {
        $assignee   = User::factory()->create(['company_id' => $this->company->id]);
        $outsider   = User::factory()->create(['company_id' => $this->company->id]);

        $task = $this->createTask([
            'company_id'              => null,
            'user_id'                 => $this->user->id,
            'is_project_notification' => true,
            'status'                  => EmployeeTaskStatus::InProgress->value,
            'time_from'               => CarbonImmutable::now(self::TZ)->subMinutes(30)->format('Y-m-d H:i:s'),
            'timezone'                => self::TZ,
            'duration_hours'          => 2,
        ]);

        \Modules\Project\ProjectManagement\Models\ProjectNotification::create([
            'company_id'               => $this->company->id,
            'employee_task_request_id' => $task->id,
            'notification_number'      => 'PN-' . uniqid(),
            'status'                   => 'in_progress',
            'assigned_user_ids'        => [(string) $assignee->id],
        ]);

        $this->assertSame(
            [],
            $this->provider->temporaryLocationsFor($outsider, CarbonImmutable::now(self::TZ))
        );
    }

    public function test_radius_defaults_to_100_when_radius_meters_is_null(): void
    {
        $this->createTask([
            'status'         => EmployeeTaskStatus::InProgress->value,
            'time_from'      => CarbonImmutable::now(self::TZ)->subMinutes(30)->format('Y-m-d H:i:s'),
            'timezone'       => self::TZ,
            'duration_hours' => 2,
            'radius_meters'  => null,
        ]);

        $locations = $this->provider->temporaryLocationsFor($this->user, CarbonImmutable::now(self::TZ));

        $this->assertCount(1, $locations);
        $this->assertSame(100, $locations[0]['radius']);
    }

    public function test_name_falls_back_per_task_type_when_title_is_empty(): void
    {
        $this->createTask([
            'status'                 => EmployeeTaskStatus::InProgress->value,
            'time_from'              => CarbonImmutable::now(self::TZ)->subMinutes(30)->format('Y-m-d H:i:s'),
            'timezone'               => self::TZ,
            'duration_hours'         => 2,
            'title'                  => null,
            'is_project_notification' => true,
        ]);

        $this->createTask([
            'status'         => EmployeeTaskStatus::InProgress->value,
            'time_from'      => CarbonImmutable::now(self::TZ)->subMinutes(30)->format('Y-m-d H:i:s'),
            'timezone'       => self::TZ,
            'duration_hours' => 2,
            'title'          => null,
        ]);

        $locations = $this->provider->temporaryLocationsFor($this->user, CarbonImmutable::now(self::TZ));

        $names = array_column($locations, 'name');
        sort($names);

        $this->assertSame(['Employee task', 'Project notification task'], $names);
    }

    public function test_is_engaged_elsewhere_true_for_active_task_within_duration(): void
    {
        $this->createTask([
            'status'         => EmployeeTaskStatus::InProgress->value,
            'time_from'      => CarbonImmutable::now(self::TZ)->subMinutes(30)->format('Y-m-d H:i:s'),
            'timezone'       => self::TZ,
            'duration_hours' => 2,
        ]);

        $this->assertTrue(
            $this->provider->isEngagedElsewhere($this->user, CarbonImmutable::now(self::TZ)),
        );
    }

    public function test_is_engaged_elsewhere_false_for_stuck_task_past_duration(): void
    {
        $this->createTask([
            'status'         => EmployeeTaskStatus::InProgress->value,
            'time_from'      => CarbonImmutable::now(self::TZ)->subHours(3)->format('Y-m-d H:i:s'),
            'timezone'       => self::TZ,
            'duration_hours' => 2,
        ]);

        $this->assertFalse(
            $this->provider->isEngagedElsewhere($this->user, CarbonImmutable::now(self::TZ)),
        );
    }

    public function test_is_engaged_elsewhere_false_for_approved_task(): void
    {
        $this->createTask([
            'status'    => EmployeeTaskStatus::Approved->value,
            'time_from' => null,
            'timezone'  => null,
        ]);

        $this->assertFalse(
            $this->provider->isEngagedElsewhere($this->user, CarbonImmutable::now(self::TZ)),
        );
    }

    public function test_expiry_honours_fractional_duration_hours(): void
    {
        $timeFrom = CarbonImmutable::now(self::TZ)->subMinutes(30);

        $this->createTask([
            'status'         => EmployeeTaskStatus::InProgress->value,
            'time_from'      => $timeFrom->format('Y-m-d H:i:s'),
            'timezone'       => self::TZ,
            'duration_hours' => 1.5,
        ]);

        $locations = $this->provider->temporaryLocationsFor($this->user, CarbonImmutable::now(self::TZ));

        $expectedExpiry = CarbonImmutable::parse($timeFrom->format('Y-m-d H:i:s'), self::TZ)
            ->addMinutes(90);

        $this->assertCount(1, $locations);
        $this->assertSame($expectedExpiry->toIso8601String(), $locations[0]['expires_at']);
    }

    private function createTask(array $overrides = []): EmployeeTaskRequest
    {
        return EmployeeTaskRequest::create(array_merge([
            'company_id'     => $this->company->id,
            'user_id'        => $this->user->id,
            'serial_number'  => 'TASK-TMPLOC-' . uniqid(),
            'title'          => 'Temporary location test task',
            'duration_hours' => 2,
            'task_date'      => CarbonImmutable::now(self::TZ)->toDateString(),
            'task_latitude'  => 24.7136,
            'task_longitude' => 46.6753,
            'status'         => EmployeeTaskStatus::InProgress->value,
        ], $overrides));
    }
}
