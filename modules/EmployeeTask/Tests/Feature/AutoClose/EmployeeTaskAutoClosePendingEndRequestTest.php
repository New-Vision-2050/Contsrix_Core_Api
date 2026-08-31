<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Tests\Feature\AutoClose;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Country\Models\Country;
use Modules\EmployeeTask\Models\EmployeeTaskEndRequest;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Models\EmployeeTaskSession;
use Modules\EmployeeTask\Services\EmployeeTaskAutoCloseService;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Tests that closeIfExpired() skips auto-rejection when a pending end request exists.
 *
 * Scenario:
 *  1. Task is in_progress.
 *  2. Employee submits an end request (status = pending).
 *  3. Duration expires → AutoCloseTaskAtDurationExpiryJob fires.
 *  4. closeIfExpired() must return false (skip) — the admin gets until midnight
 *     to review the end request.
 *
 * @group requires-db
 */
final class EmployeeTaskAutoClosePendingEndRequestTest extends TestCase
{
    use DatabaseTransactions;

    private EmployeeTaskRequest $task;
    private EmployeeTaskAutoCloseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(EmployeeTaskAutoCloseService::class);

        $country = Country::first();
        $manager = User::factory()->create();
        $company = Company::create([
            'name'               => 'Test Company ' . uniqid(),
            'user_name'          => 'test_co_' . uniqid(),
            'country_id'         => $country?->id ?? 1,
            'general_manager_id' => $manager->id,
            'is_active'          => true,
            'complete_data'      => true,
            'is_draft'           => false,
            'status'             => 'active',
        ]);
        $user    = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user);

        $tz  = 'Asia/Riyadh';
        $now = CarbonImmutable::now($tz);

        $this->task = EmployeeTaskRequest::create([
            'company_id'     => $company->id,
            'user_id'        => $user->id,
            'serial_number'  => 'TASK-TEST-' . uniqid(),
            'title'          => 'Pending end request test task',
            'duration_hours' => 4,
            'task_date'      => $now->toDateString(),
            'task_latitude'  => 24.7136,
            'task_longitude' => 46.6753,
            'status'         => 'in_progress',
            'time_from'      => $now->subHours(2)->format('Y-m-d H:i:s'),
            'timezone'       => $tz,
        ]);

        EmployeeTaskSession::create([
            'employee_task_request_id' => $this->task->id,
            'company_id'               => $company->id,
            'start_time'               => $now->subHours(2)->format('Y-m-d H:i:s'),
            'source'                   => 'manual',
        ]);
    }

    /**
     * closeIfExpired() must return false and NOT change the task status
     * when there is a pending end request.
     */
    public function test_close_if_expired_skips_when_pending_end_request_exists(): void
    {
        EmployeeTaskEndRequest::create([
            'employee_task_request_id' => $this->task->id,
            'company_id'               => $this->task->company_id,
            'requested_by'             => $this->task->user_id,
            'status'                   => 'pending',
        ]);

        $closeAt = CarbonImmutable::now('Asia/Riyadh');

        $result = $this->service->closeIfExpired($this->task, $closeAt, 'auto_duration');

        $this->assertFalse($result, 'closeIfExpired must skip when pending end request exists');

        $this->assertDatabaseHas('employee_task_requests', [
            'id'     => $this->task->id,
            'status' => 'in_progress',
        ]);
    }

    /**
     * closeIfExpired() must still work normally when there is no pending end request.
     */
    public function test_close_if_expired_works_when_no_pending_end_request(): void
    {
        $closeAt = CarbonImmutable::now('Asia/Riyadh');

        $result = $this->service->closeIfExpired($this->task, $closeAt, 'auto_duration');

        $this->assertTrue($result, 'closeIfExpired must succeed when no pending end request exists');

        $this->assertDatabaseHas('employee_task_requests', [
            'id'     => $this->task->id,
            'status' => 'rejected',
        ]);
    }

    /**
     * closeIfExpired() must skip even for out-of-location reason
     * when there is a pending end request.
     */
    public function test_close_if_expired_skips_out_of_location_when_pending_end_request_exists(): void
    {
        EmployeeTaskEndRequest::create([
            'employee_task_request_id' => $this->task->id,
            'company_id'               => $this->task->company_id,
            'requested_by'             => $this->task->user_id,
            'status'                   => 'pending',
        ]);

        $closeAt = CarbonImmutable::now('Asia/Riyadh');

        $result = $this->service->closeIfExpired($this->task, $closeAt, 'auto_location');

        $this->assertFalse($result, 'closeIfExpired must skip out-of-location when pending end request exists');

        $this->assertDatabaseHas('employee_task_requests', [
            'id'     => $this->task->id,
            'status' => 'in_progress',
        ]);
    }
}
