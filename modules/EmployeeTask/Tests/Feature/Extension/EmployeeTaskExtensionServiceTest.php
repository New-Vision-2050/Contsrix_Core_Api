<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Tests\Feature\Extension;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Modules\Company\CompanyCore\Models\Company;
use Modules\EmployeeTask\DTO\CreateExtensionRequestDTO;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Jobs\AutoCloseTaskAtDurationExpiryJob;
use Modules\EmployeeTask\Models\EmployeeTaskExtensionRequest;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Services\EmployeeTaskExtensionService;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Feature tests for EmployeeTaskExtensionService.
 *
 * Invariants under test:
 *  - INV-T8: original_duration_hours is set only on the first extension approval.
 *  - INV-T9: only one pending extension per task at a time.
 *  - INV-T10: approval re-dispatches AutoCloseTaskAtDurationExpiryJob.
 *  - last_extension_status is updated correctly on every state change.
 *
 * @group requires-db
 */
final class EmployeeTaskExtensionServiceTest extends TestCase
{
    use DatabaseTransactions;

    private EmployeeTaskRequest $task;
    private EmployeeTaskExtensionService $service;
    private User $admin;
    private User $employee;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company  = Company::factory()->create(['status' => 'active']);
        $this->employee = User::factory()->create(['company_id' => $this->company->id]);
        $this->admin    = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->employee);

        $this->service = $this->app->make(EmployeeTaskExtensionService::class);

        $now = CarbonImmutable::now('Asia/Riyadh');

        $this->task = EmployeeTaskRequest::create([
            'company_id'     => $this->company->id,
            'user_id'        => $this->employee->id,
            'serial_number'  => 'TASK-EXT-' . uniqid(),
            'title'          => 'Extension test task',
            'duration_hours' => 4,
            'task_date'      => $now->toDateString(),
            'task_latitude'  => 24.7136,
            'task_longitude' => 46.6753,
            'status'         => 'in_progress',
            'time_from'      => $now->subHours(2)->format('Y-m-d H:i:s'),
            'timezone'       => 'Asia/Riyadh',
        ]);
    }

    // -------------------------------------------------------------------------
    // requestExtension()
    // -------------------------------------------------------------------------

    public function test_request_extension_creates_pending_record(): void
    {
        $dto = new CreateExtensionRequestDTO(
            taskId:          $this->task->id,
            requestedBy:     $this->employee->id,
            additionalHours: 2.0,
            reason:          'Need more time',
        );

        $extension = $this->service->requestExtension($dto);

        $this->assertSame('pending', $extension->status);
        $this->assertSame($this->task->id, $extension->employee_task_request_id);
        $this->assertSame($this->employee->id, $extension->requested_by);
        $this->assertEqualsWithDelta(2.0, (float) $extension->additional_hours, 0.001);
    }

    public function test_request_extension_sets_last_extension_status_to_pending(): void
    {
        $dto = new CreateExtensionRequestDTO($this->task->id, $this->employee->id, 2.0, null);
        $this->service->requestExtension($dto);

        $this->assertDatabaseHas('employee_task_requests', [
            'id'                    => $this->task->id,
            'last_extension_status' => 'extension_pending',
        ]);
    }

    public function test_request_extension_throws_if_not_in_progress_or_paused(): void
    {
        $this->task->update(['status' => 'approved']);

        $this->expectException(EmployeeTaskException::class);

        $this->service->requestExtension(
            new CreateExtensionRequestDTO($this->task->id, $this->employee->id, 2.0, null)
        );
    }

    /**
     * INV-T9: only one pending extension per task at a time.
     */
    public function test_request_extension_throws_if_pending_extension_already_exists(): void
    {
        $dto = new CreateExtensionRequestDTO($this->task->id, $this->employee->id, 2.0, null);
        $this->service->requestExtension($dto);

        $this->expectException(EmployeeTaskException::class);

        $this->service->requestExtension(
            new CreateExtensionRequestDTO($this->task->id, $this->employee->id, 1.0, null)
        );
    }

    public function test_new_extension_allowed_after_rejection(): void
    {
        $dto = new CreateExtensionRequestDTO($this->task->id, $this->employee->id, 2.0, null);
        $ext = $this->service->requestExtension($dto);

        $this->service->rejectExtension($ext->id, $this->admin->id, null);

        // Must succeed without throwing.
        $dto2 = new CreateExtensionRequestDTO($this->task->id, $this->employee->id, 1.0, null);
        $ext2 = $this->service->requestExtension($dto2);

        $this->assertSame('pending', $ext2->status);
        $this->assertDatabaseHas('employee_task_requests', [
            'id'                    => $this->task->id,
            'last_extension_status' => 'extension_pending',
        ]);
    }

    // -------------------------------------------------------------------------
    // approveExtension()
    // -------------------------------------------------------------------------

    public function test_approve_extension_updates_task_duration_hours(): void
    {
        Queue::fake();

        $originalDuration = (float) $this->task->duration_hours; // 4.0
        $dto = new CreateExtensionRequestDTO($this->task->id, $this->employee->id, 2.0, null);
        $ext = $this->service->requestExtension($dto);

        $this->service->approveExtension($ext->id, $this->admin->id);

        $fresh = EmployeeTaskRequest::find($this->task->id);
        $this->assertEqualsWithDelta($originalDuration + 2.0, (float) $fresh->duration_hours, 0.001);
    }

    /**
     * INV-T8: original_duration_hours is set only on the FIRST approval.
     */
    public function test_original_duration_hours_set_only_on_first_approval(): void
    {
        Queue::fake();

        $originalDuration = (float) $this->task->duration_hours; // 4.0
        $this->assertNull($this->task->original_duration_hours);

        // First extension
        $ext1 = $this->service->requestExtension(
            new CreateExtensionRequestDTO($this->task->id, $this->employee->id, 2.0, null)
        );
        $this->service->approveExtension($ext1->id, $this->admin->id);

        $after1 = EmployeeTaskRequest::find($this->task->id);
        $this->assertEqualsWithDelta(
            $originalDuration,
            (float) $after1->original_duration_hours,
            0.001,
            'original_duration_hours must equal the pre-first-extension value'
        );

        // Second extension — original_duration_hours must NOT change
        $ext2 = $this->service->requestExtension(
            new CreateExtensionRequestDTO($this->task->id, $this->employee->id, 1.0, null)
        );
        $this->service->approveExtension($ext2->id, $this->admin->id);

        $after2 = EmployeeTaskRequest::find($this->task->id);
        $this->assertEqualsWithDelta(
            $originalDuration,
            (float) $after2->original_duration_hours,
            0.001,
            'original_duration_hours must not change on second approval'
        );
        $this->assertEqualsWithDelta(7.0, (float) $after2->duration_hours, 0.001);
    }

    public function test_approve_extension_sets_last_extension_status_to_approved(): void
    {
        Queue::fake();

        $ext = $this->service->requestExtension(
            new CreateExtensionRequestDTO($this->task->id, $this->employee->id, 2.0, null)
        );
        $this->service->approveExtension($ext->id, $this->admin->id);

        $this->assertDatabaseHas('employee_task_requests', [
            'id'                    => $this->task->id,
            'last_extension_status' => 'extension_approved',
        ]);
    }

    /**
     * INV-T10: approval re-dispatches AutoCloseTaskAtDurationExpiryJob with the new deadline.
     */
    public function test_approve_extension_redispatches_auto_close_job(): void
    {
        Queue::fake();

        $ext = $this->service->requestExtension(
            new CreateExtensionRequestDTO($this->task->id, $this->employee->id, 2.0, null)
        );
        $this->service->approveExtension($ext->id, $this->admin->id);

        Queue::assertPushed(AutoCloseTaskAtDurationExpiryJob::class);
    }

    public function test_approve_marks_extension_record_as_approved(): void
    {
        Queue::fake();

        $ext = $this->service->requestExtension(
            new CreateExtensionRequestDTO($this->task->id, $this->employee->id, 2.0, null)
        );
        $result = $this->service->approveExtension($ext->id, $this->admin->id);

        $this->assertSame('approved', $result->status);
        $this->assertSame($this->admin->id, $result->reviewed_by);
        $this->assertNotNull($result->reviewed_at);
    }

    // -------------------------------------------------------------------------
    // rejectExtension()
    // -------------------------------------------------------------------------

    public function test_reject_extension_marks_extension_as_rejected(): void
    {
        $ext = $this->service->requestExtension(
            new CreateExtensionRequestDTO($this->task->id, $this->employee->id, 2.0, null)
        );
        $result = $this->service->rejectExtension($ext->id, $this->admin->id, 'No capacity');

        $this->assertSame('rejected', $result->status);
        $this->assertSame($this->admin->id, $result->reviewed_by);
        $this->assertSame('No capacity', $result->review_notes);
    }

    public function test_reject_extension_sets_last_extension_status_to_rejected(): void
    {
        $ext = $this->service->requestExtension(
            new CreateExtensionRequestDTO($this->task->id, $this->employee->id, 2.0, null)
        );
        $this->service->rejectExtension($ext->id, $this->admin->id, null);

        $this->assertDatabaseHas('employee_task_requests', [
            'id'                    => $this->task->id,
            'last_extension_status' => 'extension_rejected',
        ]);
    }

    public function test_reject_extension_does_not_change_duration_hours(): void
    {
        $originalDuration = (float) $this->task->duration_hours;

        $ext = $this->service->requestExtension(
            new CreateExtensionRequestDTO($this->task->id, $this->employee->id, 2.0, null)
        );
        $this->service->rejectExtension($ext->id, $this->admin->id, null);

        $fresh = EmployeeTaskRequest::find($this->task->id);
        $this->assertEqualsWithDelta($originalDuration, (float) $fresh->duration_hours, 0.001);
    }

    // -------------------------------------------------------------------------
    // listExtensions()
    // -------------------------------------------------------------------------

    public function test_list_extensions_returns_all_extensions_for_task(): void
    {
        Queue::fake();

        $ext1 = $this->service->requestExtension(
            new CreateExtensionRequestDTO($this->task->id, $this->employee->id, 1.0, null)
        );
        $this->service->rejectExtension($ext1->id, $this->admin->id, null);

        $ext2 = $this->service->requestExtension(
            new CreateExtensionRequestDTO($this->task->id, $this->employee->id, 2.0, null)
        );

        $list = $this->service->listExtensions($this->task->id);

        $this->assertCount(2, $list);
    }
}
