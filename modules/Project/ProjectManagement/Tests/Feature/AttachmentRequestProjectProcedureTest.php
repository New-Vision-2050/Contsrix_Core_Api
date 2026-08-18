<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Company\CompanyCore\Models\Company;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\ProcedureSetting\Models\ProcedureSettingStepActionTaker;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\Process\Models\Process;
use Modules\Process\Services\ProcessWorkflowService;
use Modules\Project\ProjectManagement\Models\AttachmentRequest;
use Modules\Project\ProjectManagement\Models\AttachmentRequestHistory;
use Modules\Project\ProjectManagement\Models\AttachmentRequestItem;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectProcedureSetting;
use Modules\Project\ProjectManagement\Services\ProjectProcedureService;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\Shared\ResourceShare\Models\ResourceShare;
use Modules\User\Models\User;

class AttachmentRequestProjectProcedureTest extends BaseAttendanceReportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->schemaReady()) {
            $this->markTestSkipped('Attachment request project procedure schema is not migrated.');
        }

        Storage::fake('public');
        Mail::fake();
    }

    public function test_create_attachment_request_derives_folder_data_from_selected_project_procedure_without_receiver_company(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $ignoredAttachmentTypeId = (string) Str::uuid();

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests', [
                'name' => 'Shop Drawing Files',
                'date' => '2026-07-21',
                'project_id' => $project->id,
                'procedure_setting_id' => $procedure->procedure_setting_id,
                'attachment_type_id' => $ignoredAttachmentTypeId,
                'attachments' => [
                    UploadedFile::fake()->create('shop-drawing.pdf', 12, 'application/pdf'),
                ],
                'notes' => 'Created from selected project procedure',
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.procedure_setting_id', $procedure->procedure_setting_id)
            ->assertJsonPath('payload.procedure_setting.id', $procedure->procedure_setting_id)
            ->assertJsonPath('payload.procedure_setting.attachment_type.id', $procedure->attachment_type_id)
            ->assertJsonPath('payload.procedure_setting.attachment_sub_type.id', $procedure->attachment_sub_type_id)
            ->assertJsonPath('payload.procedure_setting.attachment_sub_sub_type.id', $procedure->attachment_sub_sub_type_id);

        $this->assertNotEmpty($response->json('payload.serial_number'));
        $this->assertArrayNotHasKey('receiver_company', $response->json('payload.procedure_setting'));
        $this->assertArrayNotHasKey('attachment_type_id', $response->json('payload'));
        $this->assertArrayNotHasKey('attachment_sub_type_id', $response->json('payload'));
        $this->assertArrayNotHasKey('attachment_sub_sub_type_id', $response->json('payload'));
        $this->assertArrayNotHasKey('receiver_company_id', $response->json('payload'));

        $this->assertDatabaseHas('attachment_requests', [
            'id' => $response->json('payload.id'),
            'project_id' => $project->id,
            'procedure_setting_id' => $procedure->procedure_setting_id,
            'notes' => 'Created from selected project procedure',
        ]);
    }

    public function test_create_attachment_request_rejects_procedure_from_another_project(): void
    {
        $project = $this->createProject();
        $otherProject = $this->createProject();
        $otherProcedure = $this->createProjectProcedure($otherProject);
        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests', [
                'name' => 'Wrong Project Procedure',
                'date' => '2026-07-21',
                'project_id' => $project->id,
                'procedure_setting_id' => $otherProcedure->procedure_setting_id,
                'attachments' => [
                    UploadedFile::fake()->create('wrong-project.pdf', 12, 'application/pdf'),
                ],
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['procedure_setting_id']);
    }

    public function test_attachment_request_list_does_not_expose_receiver_company_id(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);

        $this->postAttachmentRequest($project, $procedure)
            ->assertOk();

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests?project_id='.$project->id)
            ->assertOk();

        $this->assertArrayNotHasKey('receiver_company_id', $response->json('data.0'));
        $this->assertArrayNotHasKey('receiver_company', $response->json('data.0'));
    }

    public function test_attachment_request_list_history_hides_pending_workflow_steps_after_final_rejection(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $firstReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $firstReceiverUser, 1);
        $this->createProcedureStep($procedure, $secondReceiverUser, 2);

        $requestId = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->json('payload.id');

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post("/api/v1/projects/attachment-requests/{$requestId}/decline", [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.status', AttachmentRequest::STATUS_DECLINED);

        $history = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests?project_id='.$project->id.'&page=1&per_page=10')
            ->assertOk()
            ->json('data.0.history');

        $this->assertSame([
            'request_created',
            'workflow_step_rejected',
            'request_declined',
        ], collect($history)->pluck('action')->all());
        $this->assertSame((string) $firstReceiverUser->id, $history[1]['user'][0]['id']);
        $this->assertSame([], $history[2]['user']);
        $this->assertFalse(collect($history)->contains(
            static fn (array $entry): bool => $entry['action'] === 'workflow_step_pending'
        ));
    }

    public function test_attachment_request_list_history_returns_empty_user_for_final_approval(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);

        $requestId = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->json('payload.id');

        $this->actingAs($receiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post("/api/v1/projects/attachment-requests/{$requestId}/approve", [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.status', AttachmentRequest::STATUS_APPROVED);

        $history = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests?project_id='.$project->id.'&page=1&per_page=10')
            ->assertOk()
            ->json('data.0.history');

        $approvalHistory = collect($history)->firstWhere('action', 'request_approved');

        $this->assertSame([], $approvalHistory['user']);
    }

    public function test_attachment_request_list_history_keeps_pending_workflow_steps_while_in_progress(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $firstReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $firstReceiverUser, 1);
        $this->createProcedureStep($procedure, $secondReceiverUser, 2);

        $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->assertJsonPath('payload.status', AttachmentRequest::STATUS_PENDING);

        $history = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests?project_id='.$project->id.'&page=1&per_page=10')
            ->assertOk()
            ->json('data.0.history');

        $pendingHistory = collect($history)
            ->where('action', 'workflow_step_pending')
            ->values();

        $this->assertSame(2, $pendingHistory->count());
        $this->assertContains(
            (string) $firstReceiverUser->id,
            collect($pendingHistory[0]['user'])->pluck('id')->all()
        );
        $this->assertContains(
            (string) $secondReceiverUser->id,
            collect($pendingHistory[1]['user'])->pluck('id')->all()
        );
    }

    public function test_create_attachment_request_ignores_extra_receiver_company(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests', [
                'name' => 'Ignored Receiver',
                'date' => '2026-07-21',
                'project_id' => $project->id,
                'procedure_setting_id' => $procedure->procedure_setting_id,
                'receiver_company_id' => $receiverCompany->id,
                'attachments' => [
                    UploadedFile::fake()->create('ignored-receiver.pdf', 12, 'application/pdf'),
                ],
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertArrayNotHasKey('receiver_company_id', $response->json('payload'));
        $this->assertArrayNotHasKey('receiver_company', $response->json('payload'));
    }

    public function test_create_attachment_request_starts_sequence_project_procedure_workflow(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);
        $this->createProcedureStep($procedure, $receiverUser, 2);

        $response = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->assertJsonPath('payload.process.status', ProcessStatus::InProgress->value)
            ->assertJsonCount(1, 'payload.process_steps');

        $requestId = $response->json('payload.id');

        $this->assertDatabaseHas('processes', [
            'processable_id' => $requestId,
            'processable_type' => AttachmentRequest::PROCESSABLE_TYPE,
            'status' => ProcessStatus::InProgress->value,
        ]);

        $this->assertDatabaseHas('process_steps', [
            'assigned_user_id' => $receiverUser->id,
            'template_step_order' => 1,
            'status' => ProcessStepStatus::Pending->value,
        ]);
    }

    public function test_create_attachment_request_starts_parallel_project_procedure_workflow(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $firstReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);

        ProcedureSetting::query()
            ->whereKey($procedure->procedure_setting_id)
            ->update(['execute_type' => 'parallel']);

        $this->createProcedureStep($procedure, $firstReceiverUser, 1);
        $this->createProcedureStep($procedure, $secondReceiverUser, 2);

        $response = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->assertJsonPath('payload.process.execute_type', 'parallel')
            ->assertJsonCount(2, 'payload.process_steps');

        $process = Process::query()
            ->where('processable_id', $response->json('payload.id'))
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->firstOrFail();

        $this->assertSame(2, $process->steps()->where('status', ProcessStepStatus::Pending->value)->count());
    }

    public function test_receiver_company_workflow_step_uses_step_receiver_company_ids(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createReceiverCompanyProcedureStep($procedure, 1, [$receiverCompany->id]);

        $response = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->assertJsonPath('payload.process.status', ProcessStatus::InProgress->value)
            ->assertJsonCount(1, 'payload.process_steps');

        $process = Process::query()
            ->where('processable_id', $response->json('payload.id'))
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->firstOrFail();

        $step = $process->steps()->firstOrFail();
        $this->assertContains((string) $receiverUser->id, $step->authorized_user_ids);
    }

    public function test_workflow_approve_advances_steps_before_final_attachment_approval(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $firstReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $firstReceiverUser, 1);
        $this->createProcedureStep($procedure, $secondReceiverUser, 2);

        $requestId = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->json('payload.id');

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post("/api/v1/projects/attachment-requests/{$requestId}/approve", [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.status', AttachmentRequest::STATUS_PENDING)
            ->assertJsonCount(2, 'payload.process_steps');

        $this->assertDatabaseHas('attachment_requests', [
            'id' => $requestId,
            'status' => AttachmentRequest::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('attachment_request_items', [
            'attachment_request_id' => $requestId,
            'status' => AttachmentRequest::STATUS_PENDING,
        ]);

        $this->actingAs($secondReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post("/api/v1/projects/attachment-requests/{$requestId}/approve", [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.status', AttachmentRequest::STATUS_APPROVED);

        $this->assertDatabaseHas('attachment_requests', [
            'id' => $requestId,
            'status' => AttachmentRequest::STATUS_APPROVED,
        ]);
        $this->assertDatabaseHas('attachment_request_items', [
            'attachment_request_id' => $requestId,
            'status' => AttachmentRequest::STATUS_APPROVED,
        ]);
    }

    public function test_workflow_step_history_transitions_from_pending_to_approved(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $firstReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $alternateFirstReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $firstProcedureStep = $this->createProcedureStep($procedure, $firstReceiverUser, 1);
        ProcedureSettingStepActionTaker::query()->create([
            'procedure_setting_step_id' => $firstProcedureStep->id,
            'user_id' => $alternateFirstReceiverUser->id,
            'company_id' => $this->company->id,
        ]);
        $this->createProcedureStep($procedure, $secondReceiverUser, 2);

        $createResponse = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->assertJsonPath('payload.history.0.user.0.id', (string) $this->actor->id)
            ->assertJsonPath('payload.history.1.action', 'workflow_step_pending')
            ->assertJsonPath('payload.history.1.metadata.status', 'pending')
            ->assertJsonPath('payload.history.2.action', 'workflow_step_pending')
            ->assertJsonPath('payload.history.2.metadata.status', 'pending')
            ->assertJsonPath('payload.history.2.metadata.template_step_order', 2)
            ->assertJsonPath('payload.history.2.metadata.process_step_id', null)
            ->assertJsonPath('payload.history.2.user.0.id', (string) $secondReceiverUser->id);
        $this->assertHistoryUsersAreArrays($createResponse->json('payload.history'));
        $this->assertCount(2, $createResponse->json('payload.history.1.user'));
        $this->assertEqualsCanonicalizing(
            [(string) $firstReceiverUser->id, (string) $alternateFirstReceiverUser->id],
            collect($createResponse->json('payload.history.1.user'))->pluck('id')->all()
        );

        $requestId = $createResponse->json('payload.id');

        $this->assertHistoryCount($requestId, 'request_created', 1);
        $this->assertHistoryCount($requestId, 'workflow_step_pending', 2);

        $process = Process::query()
            ->where('processable_id', $requestId)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->firstOrFail();

        $this->assertSame(1, $process->steps()->count());

        $this->actingAs($secondReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post("/api/v1/projects/attachment-requests/{$requestId}/approve", [], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $firstStep = $process
            ->steps()
            ->where('template_step_order', 1)
            ->firstOrFail();

        $firstStepHistory = $this->workflowStepHistory($requestId, (string) $firstStep->id);
        $firstStepHistoryId = $firstStepHistory->id;
        $secondStepHistoryId = AttachmentRequestHistory::query()
            ->where('attachment_request_id', $requestId)
            ->where('metadata->template_step_order', 2)
            ->firstOrFail()
            ->id;

        $this->assertSame('workflow_step_pending', $firstStepHistory->action);
        $this->assertSame('pending', $firstStepHistory->metadata['status']);

        $firstApproveResponse = $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post("/api/v1/projects/attachment-requests/{$requestId}/approve", [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.history.1.action', 'workflow_step_approved')
            ->assertJsonPath('payload.history.1.metadata.template_step_order', 1)
            ->assertJsonPath('payload.history.1.metadata.status', 'approved')
            ->assertJsonPath('payload.history.1.user.0.id', (string) $firstReceiverUser->id)
            ->assertJsonPath('payload.history.2.action', 'workflow_step_pending')
            ->assertJsonPath('payload.history.2.metadata.template_step_order', 2)
            ->assertJsonPath('payload.history.2.metadata.status', 'pending')
            ->assertJsonPath('payload.history.2.user.0.id', (string) $secondReceiverUser->id);
        $this->assertHistoryUsersAreArrays($firstApproveResponse->json('payload.history'));
        $this->assertCount(1, $firstApproveResponse->json('payload.history.1.user'));

        $this->assertHistoryCount($requestId, 'workflow_step_approved', 1);
        $this->assertHistoryCount($requestId, 'workflow_step_pending', 1);
        $this->assertDatabaseHas('attachment_request_history', [
            'id' => $firstStepHistoryId,
            'attachment_request_id' => $requestId,
            'action' => 'workflow_step_approved',
            'user_id' => $firstReceiverUser->id,
        ]);
        $this->assertSame(
            1,
            AttachmentRequestHistory::query()
                ->where('attachment_request_id', $requestId)
                ->where('metadata->process_step_id', (string) $firstStep->id)
                ->count()
        );

        $secondPendingHistory = AttachmentRequestHistory::query()->findOrFail($secondStepHistoryId);
        $this->assertSame('workflow_step_pending', $secondPendingHistory->action);
        $this->assertSame(2, (int) $secondPendingHistory->metadata['template_step_order']);

        $secondApproveResponse = $this->actingAs($secondReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post("/api/v1/projects/attachment-requests/{$requestId}/approve", [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.status', AttachmentRequest::STATUS_APPROVED)
            ->assertJsonPath('payload.history.2.action', 'workflow_step_approved')
            ->assertJsonPath('payload.history.2.metadata.template_step_order', 2)
            ->assertJsonPath('payload.history.2.metadata.status', 'approved')
            ->assertJsonPath('payload.history.2.user.0.id', (string) $secondReceiverUser->id)
            ->assertJsonPath('payload.history.3.action', 'request_approved')
            ->assertJsonPath('payload.history.3.user', []);
        $this->assertHistoryUsersAreArrays($secondApproveResponse->json('payload.history'));

        $workflowStepOrders = AttachmentRequestHistory::query()
            ->where('attachment_request_id', $requestId)
            ->where('action', 'workflow_step_approved')
            ->orderBy('sort_order')
            ->get()
            ->map(static fn (AttachmentRequestHistory $history): int => (int) $history->metadata['template_step_order'])
            ->all();

        $this->assertSame([1, 2], $workflowStepOrders);
        $this->assertHistoryCount($requestId, 'workflow_step_approved', 2);
        $this->assertHistoryCount($requestId, 'workflow_step_pending', 0);
        $this->assertHistoryCount($requestId, 'request_approved', 1);
    }

    public function test_auto_approved_workflow_step_is_not_logged_but_request_approval_is_logged(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);

        $requestId = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->json('payload.id');

        $step = Process::query()
            ->where('processable_id', $requestId)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->firstOrFail()
            ->steps()
            ->where('status', ProcessStepStatus::Pending->value)
            ->firstOrFail();

        $this->assertHistoryCount($requestId, 'workflow_step_pending', 1);
        app(ProcessWorkflowService::class)->autoApproveStep((string) $step->id);

        $this->assertHistoryCount($requestId, 'request_created', 1);
        $this->assertHistoryCount($requestId, 'workflow_step_pending', 0);
        $this->assertHistoryCount($requestId, 'workflow_step_approved', 0);
        $this->assertHistoryCount($requestId, 'request_approved', 1);

        $this->assertDatabaseHas('process_steps', [
            'id' => $step->id,
            'status' => ProcessStepStatus::Approved->value,
            'action_by' => null,
        ]);
        $this->assertNotNull($step->fresh()->acted_at);
        $this->assertDatabaseHas('processes', [
            'processable_id' => $requestId,
            'processable_type' => AttachmentRequest::PROCESSABLE_TYPE,
            'status' => ProcessStatus::Completed->value,
        ]);
        $this->assertDatabaseHas('attachment_requests', [
            'id' => $requestId,
            'status' => AttachmentRequest::STATUS_APPROVED,
        ]);
    }

    public function test_auto_approved_step_is_skipped_from_history_while_manual_steps_and_final_approval_are_logged(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $autoStepUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $firstManualUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $secondManualUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $autoStepUser, 1);
        $this->createProcedureStep($procedure, $firstManualUser, 2);
        $this->createProcedureStep($procedure, $secondManualUser, 3);

        $requestId = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->json('payload.id');

        $process = Process::query()
            ->where('processable_id', $requestId)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->firstOrFail();

        $autoStep = $process->steps()
            ->where('status', ProcessStepStatus::Pending->value)
            ->where('template_step_order', 1)
            ->firstOrFail();

        $timelineStart = now();

        try {
            \Carbon\Carbon::setTestNow($timelineStart->copy()->addMinute());
            app(ProcessWorkflowService::class)->autoApproveStep((string) $autoStep->id);

            $this->assertDatabaseHas('process_steps', [
                'id' => $autoStep->id,
                'template_step_order' => 1,
                'status' => ProcessStepStatus::Approved->value,
                'action_by' => null,
            ]);

            \Carbon\Carbon::setTestNow($timelineStart->copy()->addMinutes(2));
            $this->actingAs($firstManualUser, 'api')
                ->withHeader('X-Tenant', $receiverCompany->id)
                ->post("/api/v1/projects/attachment-requests/{$requestId}/approve", [], ['Accept' => 'application/json'])
                ->assertOk()
                ->assertJsonPath('payload.status', AttachmentRequest::STATUS_PENDING);

            \Carbon\Carbon::setTestNow($timelineStart->copy()->addMinutes(3));
            $this->actingAs($secondManualUser, 'api')
                ->withHeader('X-Tenant', $receiverCompany->id)
                ->post("/api/v1/projects/attachment-requests/{$requestId}/approve", [], ['Accept' => 'application/json'])
                ->assertOk()
                ->assertJsonPath('payload.status', AttachmentRequest::STATUS_APPROVED);
        } finally {
            \Carbon\Carbon::setTestNow();
        }

        $history = AttachmentRequestHistory::query()
            ->where('attachment_request_id', $requestId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $this->assertSame([
            'request_created',
            'workflow_step_approved',
            'workflow_step_approved',
            'request_approved',
        ], $history->pluck('action')->all());

        $manualStepHistory = $history
            ->where('action', 'workflow_step_approved')
            ->values();

        $this->assertSame(2, $manualStepHistory->count());
        $this->assertSame(2, (int) $manualStepHistory[0]->metadata['template_step_order']);
        $this->assertSame((string) $firstManualUser->id, $manualStepHistory[0]->user_id);
        $this->assertSame(3, (int) $manualStepHistory[1]->metadata['template_step_order']);
        $this->assertSame((string) $secondManualUser->id, $manualStepHistory[1]->user_id);
        $this->assertSame((string) $secondManualUser->id, $history->last()->user_id);
        $this->assertFalse($history->contains(
            static fn (AttachmentRequestHistory $entry): bool => ($entry->metadata['is_auto_approved'] ?? false) === true
        ));
        $this->assertHistoryCount($requestId, 'request_created', 1);
        $this->assertHistoryCount($requestId, 'workflow_step_pending', 0);
        $this->assertHistoryCount($requestId, 'workflow_step_approved', 2);
        $this->assertHistoryCount($requestId, 'request_approved', 1);
    }

    public function test_history_writer_is_idempotent_for_same_logical_workflow_step(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);

        $requestId = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->json('payload.id');

        $this->actingAs($receiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post("/api/v1/projects/attachment-requests/{$requestId}/approve", [], ['Accept' => 'application/json'])
            ->assertOk();

        $request = AttachmentRequest::query()->findOrFail($requestId);
        $process = Process::query()
            ->where('processable_id', $requestId)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->firstOrFail();
        $actedStep = $process->steps()
            ->where('status', ProcessStepStatus::Approved->value)
            ->firstOrFail();

        $request->onWorkflowStepActionCompleted($process, $actedStep, 'approve', (string) $receiverUser->id);

        $this->assertHistoryCount($requestId, 'request_created', 1);
        $this->assertHistoryCount($requestId, 'workflow_step_approved', 1);
        $this->assertHistoryCount($requestId, 'workflow_step_pending', 0);
        $this->assertHistoryCount($requestId, 'request_approved', 1);
    }

    public function test_workflow_approval_uses_pending_step_actor_not_legacy_receiver_company_gate(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $this->actor, 1);

        $requestId = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->json('payload.id');

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post("/api/v1/projects/attachment-requests/{$requestId}/approve", [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.status', AttachmentRequest::STATUS_APPROVED);

        $this->assertDatabaseHas('process_steps', [
            'assigned_user_id' => $this->actor->id,
            'status' => ProcessStepStatus::Approved->value,
        ]);
        $this->assertDatabaseHas('attachment_requests', [
            'id' => $requestId,
            'status' => AttachmentRequest::STATUS_APPROVED,
        ]);
    }

    public function test_workflow_reject_declines_attachment_request(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);

        $requestId = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->json('payload.id');

        $step = Process::query()
            ->where('processable_id', $requestId)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->firstOrFail()
            ->steps()
            ->where('status', ProcessStepStatus::Pending->value)
            ->firstOrFail();
        $pendingHistory = $this->workflowStepHistory($requestId, (string) $step->id);

        $this->assertSame('workflow_step_pending', $pendingHistory->action);
        $this->assertSame('pending', $pendingHistory->metadata['status']);

        $declineResponse = $this->actingAs($receiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post("/api/v1/projects/attachment-requests/{$requestId}/decline", [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.status', AttachmentRequest::STATUS_DECLINED)
            ->assertJsonPath('payload.history.1.action', 'workflow_step_rejected')
            ->assertJsonPath('payload.history.1.user.0.id', (string) $receiverUser->id)
            ->assertJsonPath('payload.history.2.action', 'request_declined')
            ->assertJsonPath('payload.history.2.user', []);
        $this->assertHistoryUsersAreArrays($declineResponse->json('payload.history'));

        $this->assertDatabaseHas('processes', [
            'processable_id' => $requestId,
            'processable_type' => AttachmentRequest::PROCESSABLE_TYPE,
            'status' => ProcessStatus::Failed->value,
        ]);
        $this->assertDatabaseHas('attachment_request_items', [
            'attachment_request_id' => $requestId,
            'status' => AttachmentRequest::STATUS_DECLINED,
        ]);

        $rejectedHistory = $this->workflowStepHistory($requestId, (string) $step->id);
        $this->assertSame($pendingHistory->id, $rejectedHistory->id);
        $this->assertSame('workflow_step_rejected', $rejectedHistory->action);
        $this->assertSame('rejected', $rejectedHistory->metadata['status']);
        $this->assertSame((string) $receiverUser->id, $rejectedHistory->user_id);
        $this->assertHistoryCount($requestId, 'workflow_step_pending', 0);
        $this->assertHistoryCount($requestId, 'workflow_step_rejected', 1);
        $this->assertHistoryCount($requestId, 'request_declined', 1);
    }

    public function test_item_level_response_allowed_for_pending_step_owner(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $outsider = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);

        $response = $this->postAttachmentRequest($project, $procedure)
            ->assertOk();

        $itemId = $response->json('payload.items.0.id');

        $this->actingAs($outsider, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $itemId,
                'action' => 'approve',
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->actingAs($receiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $itemId,
                'action' => 'approve',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertDatabaseHas('attachment_request_items', [
            'id' => $itemId,
            'status' => AttachmentRequest::STATUS_APPROVED,
        ]);
    }

    public function test_item_approval_with_notes_replaces_current_pending_history_without_internal_media_history(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $firstReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $firstReceiverUser, 1);
        $this->createProcedureStep($procedure, $secondReceiverUser, 2);

        $createResponse = $this->postAttachmentRequest($project, $procedure)
            ->assertOk();

        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');
        $notes = 'Approved with notes';

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $itemId,
                'action' => 'approve',
                'notes' => $notes,
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $history = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests?project_id='.$project->id.'&page=1&per_page=10')
            ->assertOk()
            ->json('data.0.history');

        $this->assertSame([
            'request_created',
            'attachment_approved',
            'workflow_step_pending',
        ], collect($history)->pluck('action')->all());

        $approvalHistory = collect($history)->firstWhere('action', 'attachment_approved');
        $pendingHistory = collect($history)->firstWhere('action', 'workflow_step_pending');

        $this->assertSame((string) $firstReceiverUser->id, $approvalHistory['user'][0]['id']);
        $this->assertSame($notes, $approvalHistory['metadata']['response_notes']);
        $this->assertSame('pending', $approvalHistory['metadata']['previous_status']);
        $this->assertSame('approved', $approvalHistory['metadata']['status']);
        $this->assertSame($itemId, $approvalHistory['metadata']['item_id']);
        $this->assertArrayHasKey('file_url', $approvalHistory['metadata']);
        $this->assertArrayHasKey('file_name', $approvalHistory['metadata']);
        $this->assertArrayHasKey('file_path', $approvalHistory['metadata']);
        $this->assertArrayHasKey('file_size', $approvalHistory['metadata']);
        $this->assertArrayHasKey('file_type', $approvalHistory['metadata']);
        $this->assertArrayHasKey('file_size_formatted', $approvalHistory['metadata']);
        $this->assertSame((string) $secondReceiverUser->id, $pendingHistory['user'][0]['id']);
        $this->assertFalse(collect($history)->contains(
            static fn (array $entry): bool => $entry['action'] === 'media_replaced'
        ));

        $this->assertHistoryCount($requestId, 'attachment_approved', 1);
        $this->assertHistoryCount($requestId, 'media_replaced', 0);
    }

    public function test_item_approval_hides_frontend_media_replacement_history_but_preserves_audit_rows(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $firstReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $firstReceiverUser, 1);
        $this->createProcedureStep($procedure, $secondReceiverUser, 2);

        $createResponse = $this->postAttachmentRequest($project, $procedure)
            ->assertOk();

        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');
        $notes = 'Approved after final replacement';
        $finalFileSize = 24 * 1024;

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests/items/replace-media', [
                'item_id' => $itemId,
                'new_file' => UploadedFile::fake()->create('intermediate-replacement.pdf', 16, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests/items/replace-media', [
                'item_id' => $itemId,
                'new_file' => UploadedFile::fake()->create('final-replacement.pdf', 24, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.items.0.file_name', 'final-replacement.pdf');

        $this->assertHistoryCount($requestId, 'media_replaced', 2);

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $itemId,
                'action' => 'approve',
                'notes' => $notes,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.items.0.status', AttachmentRequest::STATUS_APPROVED)
            ->assertJsonPath('payload.items.0.file_name', 'final-replacement.pdf')
            ->assertJsonPath('payload.items.0.file_size', $finalFileSize)
            ->assertJsonPath('payload.items.0.response_notes', $notes);

        $history = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests?project_id='.$project->id.'&page=1&per_page=10')
            ->assertOk()
            ->json('data.0.history');

        $this->assertSame([
            'request_created',
            'attachment_approved',
            'workflow_step_pending',
        ], collect($history)->pluck('action')->all());

        $approvalHistory = collect($history)->firstWhere('action', 'attachment_approved');
        $pendingHistory = collect($history)->firstWhere('action', 'workflow_step_pending');

        $this->assertSame((string) $firstReceiverUser->id, $approvalHistory['user'][0]['id']);
        $this->assertSame($notes, $approvalHistory['metadata']['response_notes']);
        $this->assertSame($itemId, $approvalHistory['metadata']['item_id']);
        $this->assertSame('final-replacement.pdf', $approvalHistory['metadata']['file_name']);
        $this->assertSame('application/pdf', $approvalHistory['metadata']['file_type']);
        $this->assertSame($finalFileSize, $approvalHistory['metadata']['file_size']);
        $this->assertSame('24 KB', $approvalHistory['metadata']['file_size_formatted']);
        $this->assertSame((string) $secondReceiverUser->id, $pendingHistory['user'][0]['id']);
        $this->assertSame(1, collect($history)->where('action', 'attachment_approved')->count());
        $this->assertSame(0, collect($history)->where('action', 'media_replaced')->count());

        $this->assertDatabaseHas('attachment_request_items', [
            'id' => $itemId,
            'status' => AttachmentRequest::STATUS_APPROVED,
            'file_name' => 'final-replacement.pdf',
            'file_type' => 'application/pdf',
            'file_size' => $finalFileSize,
            'response_notes' => $notes,
            'responded_by_user_id' => $firstReceiverUser->id,
        ]);

        $this->assertHistoryCount($requestId, 'media_replaced', 2);
        $this->assertHistoryCount($requestId, 'attachment_approved', 1);
    }

    public function test_item_approval_history_keeps_workflow_step_order_across_sequential_approvals(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $firstReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $firstReceiverUser, 1);
        $this->createProcedureStep($procedure, $secondReceiverUser, 2);

        $createResponse = $this->postAttachmentRequest($project, $procedure)
            ->assertOk();

        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');
        $process = Process::query()
            ->where('processable_id', $requestId)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->firstOrFail();
        $initialDbSteps = $process->steps()
            ->orderBy('template_step_order')
            ->get();

        $this->assertSame(
            AttachmentRequest::STATUS_PENDING,
            AttachmentRequest::query()->findOrFail($requestId)->status
        );
        $this->assertSame(ProcessStatus::InProgress, $process->status);
        $this->assertSame(1, $initialDbSteps->count());
        $this->assertSame(1, (int) $initialDbSteps[0]->template_step_order);
        $this->assertSame(ProcessStepStatus::Pending, $initialDbSteps[0]->status);

        $initialRequest = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests?project_id='.$project->id.'&page=1&per_page=10')
            ->assertOk()
            ->json('data.0');
        $initialHistory = $initialRequest['history'];

        $this->assertSame(AttachmentRequest::STATUS_PENDING, $initialRequest['status']);
        $this->assertSame(ProcessStatus::InProgress->value, $initialRequest['process']['status']);
        $this->assertSame(ProcessStepStatus::Pending->value, $initialRequest['process_steps'][0]['status']);
        $this->assertSame('request_created', $initialHistory[0]['action']);
        $this->assertSame('workflow_step_pending', $initialHistory[1]['action']);
        $this->assertSame(1, (int) $initialHistory[1]['metadata']['template_step_order']);
        $this->assertSame((string) $firstReceiverUser->id, $initialHistory[1]['user'][0]['id']);
        $this->assertSame('workflow_step_pending', $initialHistory[2]['action']);
        $this->assertSame(2, (int) $initialHistory[2]['metadata']['template_step_order']);
        $this->assertSame((string) $secondReceiverUser->id, $initialHistory[2]['user'][0]['id']);
        $this->assertSame(
            (int) $initialHistory[1]['metadata']['process_sort_order'],
            (int) $initialHistory[2]['metadata']['process_sort_order']
        );

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests/items/replace-media', [
                'item_id' => $itemId,
                'new_file' => UploadedFile::fake()->create('step-one-draft.pdf', 16, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests/items/replace-media', [
                'item_id' => $itemId,
                'new_file' => UploadedFile::fake()->create('step-one-final.pdf', 24, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertHistoryCount($requestId, 'media_replaced', 2);

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $itemId,
                'action' => 'approve',
                'notes' => 'Step 1 approved',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $stepOneRequest = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests?project_id='.$project->id.'&page=1&per_page=10')
            ->assertOk()
            ->json('data.0');
        $stepOneHistory = $stepOneRequest['history'];
        $stepOneDbRequest = AttachmentRequest::query()->findOrFail($requestId);
        $stepOneDbProcess = $process->fresh(['steps']);
        $stepOneDbSteps = $stepOneDbProcess->steps
            ->sortBy('template_step_order')
            ->values();

        $this->assertSame(AttachmentRequest::STATUS_PENDING, $stepOneDbRequest->status);
        $this->assertNotSame(AttachmentRequest::STATUS_APPROVED, $stepOneDbRequest->status);
        $this->assertSame(ProcessStatus::InProgress, $stepOneDbProcess->status);
        $this->assertSame([1, 2], $stepOneDbSteps->pluck('template_step_order')->all());
        $this->assertSame(ProcessStepStatus::Approved, $stepOneDbSteps[0]->status);
        $this->assertSame(ProcessStepStatus::Pending, $stepOneDbSteps[1]->status);
        $this->assertSame((string) $secondReceiverUser->id, $stepOneDbSteps[1]->assigned_user_id);
        $this->assertSame(AttachmentRequest::STATUS_PENDING, $stepOneRequest['status']);
        $this->assertNotSame(AttachmentRequest::STATUS_APPROVED, $stepOneRequest['status']);
        $this->assertSame(ProcessStatus::InProgress->value, $stepOneRequest['process']['status']);
        $this->assertSame(ProcessStepStatus::Approved->value, $stepOneRequest['process_steps'][0]['status']);
        $this->assertSame(ProcessStepStatus::Pending->value, $stepOneRequest['process_steps'][1]['status']);
        $this->assertSame('request_created', $stepOneHistory[0]['action']);
        $this->assertSame('attachment_approved', $stepOneHistory[1]['action']);
        $this->assertSame(1, (int) $stepOneHistory[1]['metadata']['template_step_order']);
        $this->assertSame((string) $firstReceiverUser->id, $stepOneHistory[1]['user'][0]['id']);
        $this->assertSame('workflow_step_pending', $stepOneHistory[2]['action']);
        $this->assertSame(2, (int) $stepOneHistory[2]['metadata']['template_step_order']);
        $this->assertSame((string) $secondReceiverUser->id, $stepOneHistory[2]['user'][0]['id']);
        $this->assertSame(
            (int) $stepOneHistory[1]['metadata']['process_sort_order'],
            (int) $stepOneHistory[2]['metadata']['process_sort_order']
        );
        $this->assertFalse(collect($stepOneHistory)->contains(
            static fn (array $entry): bool => $entry['action'] === 'media_replaced'
        ));
        $this->assertFalse(collect($stepOneHistory)->contains(
            static fn (array $entry): bool => $entry['action'] === 'workflow_step_approved'
        ));
        $this->assertHistoryCount($requestId, 'attachment_approved', 1);
        $this->assertHistoryCount($requestId, 'workflow_step_pending', 1);
        $this->assertHistoryCount($requestId, 'workflow_step_approved', 0);
        $this->assertHistoryCount($requestId, 'media_replaced', 2);

        $this->actingAs($secondReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $itemId,
                'action' => 'approve',
                'notes' => 'Step 2 approved',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $stepTwoRequest = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests?project_id='.$project->id.'&page=1&per_page=10')
            ->assertOk()
            ->json('data.0');
        $stepTwoHistory = $stepTwoRequest['history'];
        $stepTwoDbRequest = AttachmentRequest::query()->findOrFail($requestId);
        $stepTwoDbProcess = $process->fresh(['steps']);
        $stepTwoDbSteps = $stepTwoDbProcess->steps
            ->sortBy('template_step_order')
            ->values();

        $this->assertSame(AttachmentRequest::STATUS_APPROVED, $stepTwoDbRequest->status);
        $this->assertSame(ProcessStatus::Completed, $stepTwoDbProcess->status);
        $this->assertSame([1, 2], $stepTwoDbSteps->pluck('template_step_order')->all());
        $this->assertSame(ProcessStepStatus::Approved, $stepTwoDbSteps[0]->status);
        $this->assertSame(ProcessStepStatus::Approved, $stepTwoDbSteps[1]->status);
        $this->assertSame(AttachmentRequest::STATUS_APPROVED, $stepTwoRequest['status']);
        $this->assertSame(ProcessStatus::Completed->value, $stepTwoRequest['process']['status']);
        $this->assertSame(ProcessStepStatus::Approved->value, $stepTwoRequest['process_steps'][0]['status']);
        $this->assertSame(ProcessStepStatus::Approved->value, $stepTwoRequest['process_steps'][1]['status']);
        $this->assertSame('request_created', $stepTwoHistory[0]['action']);
        $this->assertSame('attachment_approved', $stepTwoHistory[1]['action']);
        $this->assertSame(1, (int) $stepTwoHistory[1]['metadata']['template_step_order']);
        $this->assertSame((string) $firstReceiverUser->id, $stepTwoHistory[1]['user'][0]['id']);
        $this->assertSame('attachment_approved', $stepTwoHistory[2]['action']);
        $this->assertSame(2, (int) $stepTwoHistory[2]['metadata']['template_step_order']);
        $this->assertSame((string) $secondReceiverUser->id, $stepTwoHistory[2]['user'][0]['id']);
        $this->assertSame(2, collect($stepTwoHistory)->where('action', 'attachment_approved')->count());
        $this->assertSame(1, collect($stepTwoHistory)->where('action', 'request_approved')->count());
        $this->assertSame([], collect($stepTwoHistory)->firstWhere('action', 'request_approved')['user']);
        $this->assertFalse(collect($stepTwoHistory)->contains(
            static fn (array $entry): bool => $entry['action'] === 'media_replaced'
        ));
        $this->assertFalse(collect($stepTwoHistory)->contains(
            static fn (array $entry): bool => in_array($entry['action'], ['workflow_step_pending', 'workflow_step_approved'], true)
        ));
        $this->assertHistoryCount($requestId, 'attachment_approved', 2);
        $this->assertHistoryCount($requestId, 'workflow_step_pending', 0);
        $this->assertHistoryCount($requestId, 'workflow_step_approved', 0);
        $this->assertHistoryCount($requestId, 'media_replaced', 2);
    }

    public function test_explicit_media_replacement_still_writes_media_replaced_history(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);

        $createResponse = $this->postAttachmentRequest($project, $procedure)
            ->assertOk();

        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');

        $history = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests/items/replace-media', [
                'item_id' => $itemId,
                'new_file' => UploadedFile::fake()->create('replacement.pdf', 16, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->json('payload.history');

        $mediaHistory = collect($history)->firstWhere('action', 'media_replaced');

        $this->assertNull($mediaHistory);
        $this->assertHistoryCount($requestId, 'media_replaced', 1);
    }

    public function test_sender_can_replace_media_for_every_item_status_without_changing_decision_state(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);

        $states = [
            ['item_status' => 'pending', 'request_status' => AttachmentRequest::STATUS_PENDING],
            ['item_status' => 'update_requested', 'request_status' => AttachmentRequest::STATUS_PENDING],
            ['item_status' => 'approved', 'request_status' => AttachmentRequest::STATUS_APPROVED],
            ['item_status' => 'declined', 'request_status' => AttachmentRequest::STATUS_DECLINED],
        ];

        foreach ($states as $state) {
            $createResponse = $this->postAttachmentRequest($project, $procedure)
                ->assertOk();

            $requestId = $createResponse->json('payload.id');
            $itemId = $createResponse->json('payload.items.0.id');
            $item = AttachmentRequestItem::query()->findOrFail($itemId);

            $item->update([
                'status' => $state['item_status'],
                'responded_by_user_id' => $state['item_status'] === 'pending' ? null : $receiverUser->id,
                'responded_at' => $state['item_status'] === 'pending' ? null : now()->subMinute(),
                'response_notes' => $state['item_status'] === 'pending'
                    ? null
                    : "{$state['item_status']} response notes",
            ]);
            $item->attachmentRequest->update(['status' => $state['request_status']]);

            $beforeReplacement = $item->fresh();
            $replacementName = "{$state['item_status']}-replacement.pdf";

            $this->actingAs($this->actor, 'api')
                ->withHeader('X-Tenant', $this->company->id)
                ->post('/api/v1/projects/attachment-requests/items/replace-media', [
                    'item_id' => $itemId,
                    'new_file' => UploadedFile::fake()->create($replacementName, 16, 'application/pdf'),
                ], ['Accept' => 'application/json'])
                ->assertOk()
                ->assertJsonPath('payload.status', $state['request_status'])
                ->assertJsonPath('payload.items.0.status', $state['item_status'])
                ->assertJsonPath('payload.items.0.file_name', $replacementName);

            $replacedItem = $item->fresh();
            $replacedRequest = AttachmentRequest::query()->findOrFail($requestId);

            $this->assertSame($state['item_status'], $replacedItem->status);
            $this->assertSame($state['request_status'], $replacedRequest->status);
            $this->assertSame($replacementName, $replacedItem->file_name);
            $this->assertSame('application/pdf', $replacedItem->file_type);
            $this->assertSame(16 * 1024, $replacedItem->file_size);
            $this->assertSame($beforeReplacement->responded_by_user_id, $replacedItem->responded_by_user_id);
            $this->assertEquals($beforeReplacement->responded_at, $replacedItem->responded_at);
            $this->assertSame($beforeReplacement->response_notes, $replacedItem->response_notes);
            $this->assertHistoryCount($requestId, 'media_replaced', 1);
        }
    }

    public function test_visible_receiver_cannot_replace_attachment_media(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);

        $createResponse = $this->postAttachmentRequest($project, $procedure)
            ->assertOk();

        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');
        $beforeReplacement = AttachmentRequestItem::query()->findOrFail($itemId);
        $mediaCount = $beforeReplacement->getMedia('attachments')->count();

        $this->actingAs($receiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post('/api/v1/projects/attachment-requests/items/replace-media', [
                'item_id' => $itemId,
                'new_file' => UploadedFile::fake()->create('unauthorized-replacement.pdf', 16, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(403);

        $unchangedItem = $beforeReplacement->fresh();

        $this->assertSame($beforeReplacement->file_name, $unchangedItem->file_name);
        $this->assertSame($beforeReplacement->file_type, $unchangedItem->file_type);
        $this->assertSame($beforeReplacement->file_size, $unchangedItem->file_size);
        $this->assertSame($mediaCount, $unchangedItem->getMedia('attachments')->count());
        $this->assertHistoryCount($requestId, 'media_replaced', 0);
    }

    public function test_historical_attachment_request_history_migration_normalizes_old_item_approval_history(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $firstReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $firstReceiverUser, 1);
        $this->createProcedureStep($procedure, $secondReceiverUser, 2);

        $createResponse = $this->postAttachmentRequest($project, $procedure)
            ->assertOk();

        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $itemId,
                'action' => 'approve',
                'notes' => 'Historical step 1 approval',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $process = Process::query()
            ->where('processable_id', $requestId)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->firstOrFail();

        DB::table('processes')
            ->where('id', $process->id)
            ->update(['sort_order' => 7]);

        $process = $process->fresh(['steps']);
        $steps = $process->steps->sortBy('template_step_order')->values();
        $stepOne = $steps[0];
        $stepTwo = $steps[1];

        DB::table('attachment_requests')
            ->where('id', $requestId)
            ->update(['status' => AttachmentRequest::STATUS_APPROVED]);

        $legacyApprovalMetadata = [
            'item_id' => $itemId,
            'file_name' => 'workflow-file.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 12 * 1024,
            'status' => AttachmentRequest::STATUS_APPROVED,
            'response_notes' => 'Historical step 1 approval',
            'previous_status' => AttachmentRequest::STATUS_PENDING,
        ];

        $approvalHistory = AttachmentRequestHistory::query()
            ->where('attachment_request_id', $requestId)
            ->where('action', 'attachment_approved')
            ->firstOrFail();

        DB::table('attachment_request_history')
            ->where('id', $approvalHistory->id)
            ->update([
                'metadata' => $this->historyJson($legacyApprovalMetadata),
                'dedupe_key' => null,
                'sort_order' => null,
                'created_at' => now()->addMinutes(5)->toDateTimeString(),
            ]);

        $this->insertHistoricalHistory(
            requestId: $requestId,
            action: 'attachment_approved',
            description: 'Duplicate historical attachment approval',
            userId: (string) $firstReceiverUser->id,
            itemId: $itemId,
            metadata: $legacyApprovalMetadata,
            createdAt: now()->addMinutes(6),
        );

        $stalePendingId = $this->insertHistoricalHistory(
            requestId: $requestId,
            action: 'workflow_step_pending',
            description: 'Old stale pending step 1',
            userId: null,
            itemId: null,
            metadata: [
                'process_id' => (string) $process->id,
                'step_id' => (int) $stepOne->step_id,
                'template_step_order' => 1,
                'assigned_user_id' => (string) $firstReceiverUser->id,
                'authorized_user_ids' => [(string) $firstReceiverUser->id],
                'status' => ProcessStepStatus::Pending->value,
            ],
            createdAt: now()->subMinutes(10),
            sortOrder: 107000,
        );

        $this->insertHistoricalHistory(
            requestId: $requestId,
            action: 'media_replaced',
            description: 'Old media replacement',
            userId: (string) $firstReceiverUser->id,
            itemId: $itemId,
            metadata: ['item_id' => $itemId, 'new_file_name' => 'old-draft.pdf'],
            createdAt: now()->subMinutes(8),
        );

        $this->insertHistoricalHistory(
            requestId: $requestId,
            action: 'media_replaced',
            description: 'Old media replacement',
            userId: (string) $firstReceiverUser->id,
            itemId: $itemId,
            metadata: ['item_id' => $itemId, 'new_file_name' => 'old-final.pdf'],
            createdAt: now()->subMinutes(7),
        );

        $this->assertSame(2, $this->historyCountForItem($requestId, 'media_replaced', $itemId));
        $this->assertHistoryCount($requestId, 'attachment_approved', 2);
        $this->assertDatabaseHas('attachment_requests', [
            'id' => $requestId,
            'status' => AttachmentRequest::STATUS_APPROVED,
        ]);

        $this->runHistoricalAttachmentRequestHistoryMigration();

        $this->assertSame(2, $this->historyCountForItem($requestId, 'media_replaced', $itemId));
        $this->assertHistoryCount($requestId, 'attachment_approved', 1);
        $this->assertDatabaseHas('attachment_requests', [
            'id' => $requestId,
            'status' => AttachmentRequest::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('processes', [
            'id' => $process->id,
            'status' => ProcessStatus::InProgress->value,
        ]);
        $this->assertDatabaseHas('process_steps', [
            'id' => $stepOne->id,
            'status' => ProcessStepStatus::Approved->value,
        ]);
        $this->assertDatabaseHas('process_steps', [
            'id' => $stepTwo->id,
            'status' => ProcessStepStatus::Pending->value,
        ]);

        $approvalAfterMigration = AttachmentRequestHistory::query()
            ->where('attachment_request_id', $requestId)
            ->where('action', 'attachment_approved')
            ->firstOrFail();
        $approvalMetadata = $approvalAfterMigration->metadata;

        $this->assertSame(7, (int) $approvalMetadata['process_sort_order']);
        $this->assertSame(1, (int) $approvalMetadata['template_step_order']);
        $this->assertSame((string) $stepOne->id, $approvalMetadata['process_step_id']);
        $this->assertSame(107001, (int) $approvalAfterMigration->sort_order);

        $stalePendingMetadata = AttachmentRequestHistory::query()
            ->findOrFail($stalePendingId)
            ->metadata;
        $this->assertSame((string) $stepOne->id, $stalePendingMetadata['process_step_id']);

        $stepTwoPending = AttachmentRequestHistory::query()
            ->where('attachment_request_id', $requestId)
            ->where('action', 'workflow_step_pending')
            ->where('metadata->template_step_order', 2)
            ->firstOrFail();
        $this->assertSame(7, (int) $stepTwoPending->metadata['process_sort_order']);
        $this->assertSame(107002, (int) $stepTwoPending->sort_order);

        $apiRequest = $this->fetchAttachmentRequestFromList($project);
        $history = $apiRequest['history'];

        $this->assertSame(AttachmentRequest::STATUS_PENDING, $apiRequest['status']);
        $this->assertSame([
            'request_created',
            'attachment_approved',
            'workflow_step_pending',
        ], collect($history)->pluck('action')->all());
        $this->assertSame(0, collect($history)->where('action', 'media_replaced')->count());
        $this->assertSame(1, (int) $history[1]['metadata']['template_step_order']);
        $this->assertSame(2, (int) $history[2]['metadata']['template_step_order']);
        $this->assertLessThan(
            collect($history)->search(static fn (array $entry): bool => $entry['action'] === 'workflow_step_pending'),
            collect($history)->search(static fn (array $entry): bool => $entry['action'] === 'attachment_approved')
        );
    }

    public function test_historical_attachment_request_history_migration_preserves_standalone_media_replacement(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);

        $createResponse = $this->postAttachmentRequest($project, $procedure)
            ->assertOk();

        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');

        $this->insertHistoricalHistory(
            requestId: $requestId,
            action: 'media_replaced',
            description: 'Standalone replacement',
            userId: (string) $receiverUser->id,
            itemId: $itemId,
            metadata: ['item_id' => $itemId, 'new_file_name' => 'standalone.pdf'],
            createdAt: now(),
        );

        $this->runHistoricalAttachmentRequestHistoryMigration();

        $this->assertSame(1, $this->historyCountForItem($requestId, 'media_replaced', $itemId));

        $history = $this->fetchAttachmentRequestFromList($project)['history'];

        $this->assertSame(0, collect($history)->where('action', 'media_replaced')->count());
    }

    public function test_historical_attachment_request_history_migration_preserves_other_item_media_replacement(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $firstReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $firstReceiverUser, 1);
        $this->createProcedureStep($procedure, $secondReceiverUser, 2);

        $createResponse = $this->postAttachmentRequest($project, $procedure)
            ->assertOk();

        $requestId = $createResponse->json('payload.id');
        $itemAId = $createResponse->json('payload.items.0.id');
        $itemBId = $this->createHistoricalAttachmentItem($requestId, 'other-item.pdf');

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $itemAId,
                'action' => 'approve',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->insertHistoricalHistory(
            requestId: $requestId,
            action: 'media_replaced',
            description: 'Approval-related replacement',
            userId: (string) $firstReceiverUser->id,
            itemId: $itemAId,
            metadata: ['item_id' => $itemAId, 'new_file_name' => 'approved-item.pdf'],
            createdAt: now(),
        );
        $this->insertHistoricalHistory(
            requestId: $requestId,
            action: 'media_replaced',
            description: 'Other item replacement',
            userId: (string) $firstReceiverUser->id,
            itemId: $itemBId,
            metadata: ['item_id' => $itemBId, 'new_file_name' => 'other-item.pdf'],
            createdAt: now(),
        );

        $this->runHistoricalAttachmentRequestHistoryMigration();

        $this->assertSame(1, $this->historyCountForItem($requestId, 'media_replaced', $itemAId));
        $this->assertSame(1, $this->historyCountForItem($requestId, 'media_replaced', $itemBId));

        $history = $this->fetchAttachmentRequestFromList($project)['history'];

        $this->assertSame(0, collect($history)->where('action', 'media_replaced')->count());
    }

    public function test_historical_attachment_request_history_migration_preserves_completed_approval_and_final_user_presentation(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);

        $requestId = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->json('payload.id');

        $this->actingAs($receiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post("/api/v1/projects/attachment-requests/{$requestId}/approve", [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.status', AttachmentRequest::STATUS_APPROVED);

        $requestApprovedHistory = AttachmentRequestHistory::query()
            ->where('attachment_request_id', $requestId)
            ->where('action', 'request_approved')
            ->firstOrFail();

        $this->assertSame((string) $receiverUser->id, (string) $requestApprovedHistory->user_id);

        $this->runHistoricalAttachmentRequestHistoryMigration();

        $this->assertDatabaseHas('attachment_requests', [
            'id' => $requestId,
            'status' => AttachmentRequest::STATUS_APPROVED,
        ]);
        $this->assertDatabaseHas('processes', [
            'processable_id' => $requestId,
            'processable_type' => AttachmentRequest::PROCESSABLE_TYPE,
            'status' => ProcessStatus::Completed->value,
        ]);
        $this->assertDatabaseHas('attachment_request_history', [
            'id' => $requestApprovedHistory->id,
            'user_id' => $receiverUser->id,
        ]);

        $history = $this->fetchAttachmentRequestFromList($project)['history'];
        $approvalHistory = collect($history)->firstWhere('action', 'request_approved');

        $this->assertSame([], $approvalHistory['user']);
    }

    public function test_historical_attachment_request_history_migration_leaves_declined_pending_history_to_presenter(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $firstReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $firstReceiverUser, 1);
        $this->createProcedureStep($procedure, $secondReceiverUser, 2);

        $requestId = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->json('payload.id');

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post("/api/v1/projects/attachment-requests/{$requestId}/decline", [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.status', AttachmentRequest::STATUS_DECLINED);

        $pendingIdsBefore = AttachmentRequestHistory::query()
            ->where('attachment_request_id', $requestId)
            ->where('action', 'workflow_step_pending')
            ->pluck('id')
            ->all();

        $this->assertNotEmpty($pendingIdsBefore);

        $this->runHistoricalAttachmentRequestHistoryMigration();

        $pendingIdsAfter = AttachmentRequestHistory::query()
            ->where('attachment_request_id', $requestId)
            ->where('action', 'workflow_step_pending')
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing($pendingIdsBefore, $pendingIdsAfter);

        $history = $this->fetchAttachmentRequestFromList($project)['history'];

        $this->assertSame([
            'request_created',
            'workflow_step_rejected',
            'request_declined',
        ], collect($history)->pluck('action')->all());
        $this->assertSame([], collect($history)->firstWhere('action', 'request_declined')['user']);
    }

    public function test_historical_attachment_request_history_migration_is_idempotent_for_already_clean_data(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $firstReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $firstReceiverUser, 1);
        $this->createProcedureStep($procedure, $secondReceiverUser, 2);

        $createResponse = $this->postAttachmentRequest($project, $procedure)
            ->assertOk();

        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $itemId,
                'action' => 'approve',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $historyBefore = $this->historySnapshot($requestId);
        $requestBefore = $this->requestSnapshot($requestId);
        $processBefore = $this->processSnapshot($requestId);
        $stepsBefore = $this->processStepsSnapshot($requestId);

        $this->runHistoricalAttachmentRequestHistoryMigration();
        $this->runHistoricalAttachmentRequestHistoryMigration();

        $this->assertSame($historyBefore, $this->historySnapshot($requestId));
        $this->assertSame($requestBefore, $this->requestSnapshot($requestId));
        $this->assertSame($processBefore, $this->processSnapshot($requestId));
        $this->assertSame($stepsBefore, $this->processStepsSnapshot($requestId));
        $this->assertHistoryCount($requestId, 'attachment_approved', 1);
        $this->assertHistoryCount($requestId, 'media_replaced', 0);
    }

    public function test_historical_final_approval_status_migration_marks_only_eligible_requests_approved(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);

        $eligibleRequestId = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->json('payload.id');
        $pendingStepRequestId = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->json('payload.id');
        $recentRequestId = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->json('payload.id');

        foreach ([$eligibleRequestId, $pendingStepRequestId, $recentRequestId] as $requestId) {
            DB::table('attachment_request_history')
                ->where('attachment_request_id', $requestId)
                ->delete();

            $this->insertHistoricalHistory(
                requestId: $requestId,
                action: 'request_created',
                description: 'Legacy request created',
                userId: (string) $this->actor->id,
                itemId: null,
                metadata: [],
                createdAt: new \DateTimeImmutable('2026-08-13 09:00:00'),
                sortOrder: 0,
            );
            $this->insertHistoricalHistory(
                requestId: $requestId,
                action: 'attachment_approved',
                description: 'Legacy final attachment approval',
                userId: (string) $receiverUser->id,
                itemId: null,
                metadata: ['status' => 'approved'],
                createdAt: new \DateTimeImmutable('2026-08-13 10:00:00'),
                sortOrder: 101001,
            );
        }

        $this->insertHistoricalHistory(
            requestId: $pendingStepRequestId,
            action: 'workflow_step_pending',
            description: 'Workflow step still pending',
            userId: null,
            itemId: null,
            metadata: ['status' => 'pending'],
            createdAt: new \DateTimeImmutable('2026-08-13 11:00:00'),
            sortOrder: 101002,
        );

        DB::table('attachment_requests')
            ->whereIn('id', [$eligibleRequestId, $pendingStepRequestId])
            ->update([
                'status' => AttachmentRequest::STATUS_PENDING,
                'created_at' => '2026-08-13 12:00:00',
            ]);
        DB::table('attachment_requests')
            ->where('id', $recentRequestId)
            ->update([
                'status' => AttachmentRequest::STATUS_PENDING,
                'created_at' => '2026-08-14 00:00:00',
            ]);

        $lastEligibleHistory = DB::table('attachment_request_history')
            ->where('attachment_request_id', $eligibleRequestId)
            ->orderByRaw('sort_order is null desc')
            ->orderByDesc('sort_order')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $this->assertSame('attachment_approved', $lastEligibleHistory->action);
        $this->assertSame(
            '2026-08-13 12:00:00',
            DB::table('attachment_requests')->where('id', $eligibleRequestId)->value('created_at')
        );

        $this->runHistoricalFinalApprovalStatusMigration();

        $this->assertDatabaseHas('attachment_requests', [
            'id' => $eligibleRequestId,
            'status' => AttachmentRequest::STATUS_APPROVED,
        ]);
        $this->assertDatabaseHas('attachment_requests', [
            'id' => $pendingStepRequestId,
            'status' => AttachmentRequest::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('attachment_requests', [
            'id' => $recentRequestId,
            'status' => AttachmentRequest::STATUS_PENDING,
        ]);
    }

    public function test_legacy_pending_workflow_migration_removes_only_history_for_completed_step(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $firstReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $thirdReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $firstReceiverUser, 1);
        $this->createProcedureStep($procedure, $secondReceiverUser, 2);
        $this->createProcedureStep($procedure, $thirdReceiverUser, 3);

        $createResponse = $this->postAttachmentRequest($project, $procedure)
            ->assertOk();
        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post("/api/v1/projects/attachment-requests/{$requestId}/approve", [], ['Accept' => 'application/json'])
            ->assertOk();

        $this->actingAs($secondReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $itemId,
                'action' => 'approve',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $process = Process::query()
            ->where('processable_id', $requestId)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->firstOrFail()
            ->load('steps');
        $steps = $process->steps->sortBy('template_step_order')->values();
        $secondStep = $steps[1];
        $thirdStep = $steps[2];
        $processSortOrder = (int) ($process->sort_order ?? 0);

        $stalePendingHistoryId = $this->insertHistoricalHistory(
            requestId: $requestId,
            action: 'workflow_step_pending',
            description: 'Legacy stale pending step two',
            userId: null,
            itemId: null,
            metadata: [
                'process_id' => (string) $process->id,
                'process_sort_order' => $processSortOrder,
                'process_step_id' => null,
                'step_id' => (int) $secondStep->step_id,
                'template_step_order' => (int) $secondStep->template_step_order,
                'status' => ProcessStepStatus::Pending->value,
            ],
            createdAt: now(),
            sortOrder: 100000 + ($processSortOrder * 1000) + (int) $secondStep->template_step_order,
        );

        DB::table('attachment_requests')
            ->where('id', $requestId)
            ->update([
                'status' => AttachmentRequest::STATUS_APPROVED,
                'created_at' => '2026-08-13 12:00:00',
            ]);

        $this->runLegacyPendingWorkflowStatusRepairMigration();

        $this->assertDatabaseHas('attachment_requests', [
            'id' => $requestId,
            'status' => AttachmentRequest::STATUS_PENDING,
        ]);
        $this->assertDatabaseMissing('attachment_request_history', [
            'id' => $stalePendingHistoryId,
        ]);
        $remainingPendingHistory = AttachmentRequestHistory::query()
            ->where('attachment_request_id', $requestId)
            ->where('action', 'workflow_step_pending')
            ->get()
            ->filter(static fn (AttachmentRequestHistory $history): bool =>
                (int) ($history->metadata['template_step_order'] ?? 0) === (int) $thirdStep->template_step_order
            );
        $this->assertCount(1, $remainingPendingHistory);

        $history = $this->fetchAttachmentRequestFromList($project)['history'];

        $this->assertSame([
            'request_created',
            'workflow_step_approved',
            'attachment_approved',
            'workflow_step_pending',
        ], collect($history)->pluck('action')->all());
        $this->assertSame(
            (int) $thirdStep->template_step_order,
            (int) collect($history)->last()['metadata']['template_step_order']
        );
    }

    public function test_approval_without_resolvable_workflow_steps_auto_approves(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);

        $response = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->assertJsonPath('payload.status', AttachmentRequest::STATUS_APPROVED);

        $this->assertDatabaseHas('attachment_request_items', [
            'attachment_request_id' => $response->json('payload.id'),
            'status' => AttachmentRequest::STATUS_APPROVED,
        ]);
    }

    public function test_incoming_requests_are_visible_to_workflow_action_taker(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);

        $this->postAttachmentRequest($project, $procedure)->assertOk();

        $response = $this->actingAs($receiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->getJson('/api/v1/projects/attachment-requests?project_id='.$project->id.'&direction=incoming')
            ->assertOk();

        $this->assertNotEmpty($response->json('data'));
    }

    public function test_attachment_request_visibility_uses_project_procedure_receiver_companies(): void
    {
        $project = $this->createProject();
        $companyA = $this->createCompany(['serial_no' => 'ATT-VIS-A']);
        $companyB = $this->createCompany(['serial_no' => 'ATT-VIS-B']);
        $companyC = $this->createCompany(['serial_no' => 'ATT-VIS-C']);
        $companyD = $this->createCompany(['serial_no' => 'ATT-VIS-D']);
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $userB = User::factory()->create(['company_id' => $companyB->id]);
        $userC = User::factory()->create(['company_id' => $companyC->id]);
        $userD = User::factory()->create(['company_id' => $companyD->id]);

        foreach ([$companyA, $companyB, $companyC, $companyD] as $company) {
            $this->createAcceptedShare($project, $company);
        }

        $procedure = $this->createProjectProcedure($project, [$companyA->id, $companyC->id]);
        $this->createProcedureStep($procedure, $userB, 1);

        $createResponse = $this->postAttachmentRequest($project, $procedure)->assertOk();
        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');

        foreach ([[$userA, $companyA], [$userC, $companyC]] as [$user, $company]) {
            $ids = collect($this->actingAs($user, 'api')
                ->withHeader('X-Tenant', $company->id)
                ->getJson('/api/v1/projects/attachment-requests?project_id='.$project->id.'&direction=incoming')
                ->assertOk()
                ->json('data'))->pluck('id')->all();

            $this->assertContains($requestId, $ids);

            $pendingIds = collect($this->actingAs($user, 'api')
                ->withHeader('X-Tenant', $company->id)
                ->getJson('/api/v1/projects/attachment-requests/incoming/pending?project_id='.$project->id)
                ->assertOk()
                ->json('payload'))->pluck('id')->all();

            $this->assertContains($requestId, $pendingIds);

            $this->actingAs($user, 'api')
                ->withHeader('X-Tenant', $company->id)
                ->getJson('/api/v1/projects/attachment-requests/count?project_id='.$project->id)
                ->assertOk()
                ->assertJsonPath('count', 1);

            $this->actingAs($user, 'api')
                ->withHeader('X-Tenant', $company->id)
                ->getJson("/api/v1/projects/attachment-requests/{$requestId}")
                ->assertOk()
                ->assertJsonPath('payload.id', $requestId);
        }

        foreach ([[$userB, $companyB], [$userD, $companyD]] as [$user, $company]) {
            $ids = collect($this->actingAs($user, 'api')
                ->withHeader('X-Tenant', $company->id)
                ->getJson('/api/v1/projects/attachment-requests?project_id='.$project->id.'&direction=incoming')
                ->assertOk()
                ->json('data'))->pluck('id')->all();

            $this->assertNotContains($requestId, $ids);

            $pendingIds = collect($this->actingAs($user, 'api')
                ->withHeader('X-Tenant', $company->id)
                ->getJson('/api/v1/projects/attachment-requests/incoming/pending?project_id='.$project->id)
                ->assertOk()
                ->json('payload'))->pluck('id')->all();

            $this->assertNotContains($requestId, $pendingIds);

            $this->actingAs($user, 'api')
                ->withHeader('X-Tenant', $company->id)
                ->getJson('/api/v1/projects/attachment-requests/count?project_id='.$project->id)
                ->assertOk()
                ->assertJsonPath('count', 0);

            $this->actingAs($user, 'api')
                ->withHeader('X-Tenant', $company->id)
                ->getJson("/api/v1/projects/attachment-requests/{$requestId}")
                ->assertForbidden();

            $this->actingAs($user, 'api')
                ->withHeader('X-Tenant', $company->id)
                ->post("/api/v1/projects/attachment-requests/{$requestId}/approve", [], ['Accept' => 'application/json'])
                ->assertForbidden();

            $this->actingAs($user, 'api')
                ->withHeader('X-Tenant', $company->id)
                ->post("/api/v1/projects/attachment-requests/{$requestId}/decline", [], ['Accept' => 'application/json'])
                ->assertForbidden();

            $this->actingAs($user, 'api')
                ->withHeader('X-Tenant', $company->id)
                ->post('/api/v1/projects/attachment-requests/items/respond', [
                    'item_id' => $itemId,
                    'action' => 'approve',
                ], ['Accept' => 'application/json'])
                ->assertForbidden();
        }

        $ownerIds = collect($this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests?project_id='.$project->id.'&direction=outgoing')
            ->assertOk()
            ->json('data'))->pluck('id')->all();

        $this->assertContains($requestId, $ownerIds);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/attachment-requests/{$requestId}")
            ->assertOk()
            ->assertJsonPath('payload.id', $requestId);
    }

    public function test_empty_project_procedure_receivers_keep_legacy_shared_company_visibility(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $companyA = $this->createCompany(['serial_no' => 'ATT-LEG-A']);
        $companyB = $this->createCompany(['serial_no' => 'ATT-LEG-B']);
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $userB = User::factory()->create(['company_id' => $companyB->id]);

        $this->createAcceptedShare($project, $companyA);
        $this->createAcceptedShare($project, $companyB);

        $requestId = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->json('payload.id');

        foreach ([[$userA, $companyA], [$userB, $companyB]] as [$user, $company]) {
            $ids = collect($this->actingAs($user, 'api')
                ->withHeader('X-Tenant', $company->id)
                ->getJson('/api/v1/projects/attachment-requests?project_id='.$project->id.'&direction=incoming')
                ->assertOk()
                ->json('data'))->pluck('id')->all();

            $this->assertContains($requestId, $ids);

            $this->actingAs($user, 'api')
                ->withHeader('X-Tenant', $company->id)
                ->getJson("/api/v1/projects/attachment-requests/{$requestId}")
                ->assertOk()
                ->assertJsonPath('payload.id', $requestId);
        }
    }

    public function test_selectable_procedures_endpoint_returns_project_procedures(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests/procedures?project_id='.$project->id)
            ->assertOk()
            ->assertJsonPath('payload.0.procedure_setting_id', $procedure->procedure_setting_id);
    }

    public function test_attachment_request_selectable_procedures_are_filtered_by_receiver_companies(): void
    {
        $project = $this->createProject();
        $companyA = $this->createCompany(['serial_no' => 'ATT-PROC-A']);
        $companyB = $this->createCompany(['serial_no' => 'ATT-PROC-B']);
        $companyC = $this->createCompany(['serial_no' => 'ATT-PROC-C']);
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $userB = User::factory()->create(['company_id' => $companyB->id]);

        foreach ([$companyA, $companyB, $companyC] as $company) {
            $this->createAcceptedShare($project, $company);
        }

        $unrestricted = $this->createProjectProcedure($project);
        $restricted = $this->createProjectProcedure($project, [$companyA->id, $companyC->id]);

        $ownerIds = collect($this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests/procedures?project_id='.$project->id)
            ->assertOk()
            ->json('payload'))->pluck('procedure_setting_id')->all();

        $companyAIds = collect($this->actingAs($userA, 'api')
            ->withHeader('X-Tenant', $companyA->id)
            ->getJson('/api/v1/projects/attachment-requests/procedures?project_id='.$project->id)
            ->assertOk()
            ->json('payload'))->pluck('procedure_setting_id')->all();

        $companyBIds = collect($this->actingAs($userB, 'api')
            ->withHeader('X-Tenant', $companyB->id)
            ->getJson('/api/v1/projects/attachment-requests/procedures?project_id='.$project->id)
            ->assertOk()
            ->json('payload'))->pluck('procedure_setting_id')->all();

        $this->assertContains($unrestricted->procedure_setting_id, $ownerIds);
        $this->assertContains($restricted->procedure_setting_id, $ownerIds);
        $this->assertContains($unrestricted->procedure_setting_id, $companyAIds);
        $this->assertContains($restricted->procedure_setting_id, $companyAIds);
        $this->assertContains($unrestricted->procedure_setting_id, $companyBIds);
        $this->assertNotContains($restricted->procedure_setting_id, $companyBIds);
    }

    public function test_item_approval_history_scope_is_full_for_a_single_file_and_is_exposed_by_the_history_api(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);

        $createResponse = $this->postAttachmentRequest($project, $procedure)->assertOk();
        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');

        $this->respondToAttachmentItem($receiverUser, $receiverCompany, $itemId, 'approve');

        $history = $this->attachmentItemHistory($requestId, 'attachment_approved', $itemId);
        $this->assertSame('full', $history->metadata['decision_scope']);

        $apiHistory = collect($this->fetchAttachmentRequestFromList($project)['history'])
            ->firstWhere('id', $history->id);

        $this->assertSame('full', $apiHistory['metadata']['decision_scope']);
    }

    public function test_full_single_file_item_decline_finalizes_the_workflow_and_history_once(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);

        $createResponse = $this->postAttachmentRequest($project, $procedure)->assertOk();
        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');

        $this->respondToAttachmentItem($receiverUser, $receiverCompany, $itemId, 'decline');

        $history = $this->attachmentItemHistory($requestId, 'attachment_declined', $itemId);
        $this->assertSame('full', $history->metadata['decision_scope']);

        $process = Process::query()
            ->where('processable_id', $requestId)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->with('steps')
            ->firstOrFail();

        $this->assertSame(ProcessStatus::Failed, $process->status);
        $this->assertSame(ProcessStepStatus::Rejected, $process->steps->sole()->status);
        $this->assertSame((string) $receiverUser->id, (string) $process->steps->sole()->action_by);
        $this->assertHistoryCount($requestId, 'request_declined', 1);
        $this->assertHistoryCount($requestId, 'workflow_step_rejected', 1);
        $this->assertHistoryCount($requestId, 'workflow_step_pending', 0);

        $historyResponse = $this->fetchAttachmentRequestFromList($project);
        $this->assertSame(AttachmentRequest::STATUS_DECLINED, $historyResponse['status']);
        $this->assertSame([
            'request_created',
            'attachment_declined',
            'request_declined',
        ], collect($historyResponse['history'])->pluck('action')->all());
        $this->assertSame('full', $historyResponse['history'][1]['metadata']['decision_scope']);
        $this->assertSame([], $historyResponse['history'][2]['user']);
        $this->assertFalse(collect($historyResponse['history'])->contains(
            static fn (array $entry): bool => $entry['action'] === 'workflow_step_rejected'
        ));

        $this->respondToAttachmentItem($receiverUser, $receiverCompany, $itemId, 'decline');

        $this->assertHistoryCount($requestId, 'workflow_step_rejected', 1);
        $this->assertHistoryCount($requestId, 'request_declined', 1);
    }

    public function test_item_approval_history_scope_changes_from_partial_to_full_when_all_two_files_are_approved(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);
        $this->createProcedureStep($procedure, $receiverUser, 2);

        $createResponse = $this->postAttachmentRequest($project, $procedure, 2)->assertOk();
        $requestId = $createResponse->json('payload.id');
        $firstItemId = $createResponse->json('payload.items.0.id');
        $secondItemId = $createResponse->json('payload.items.1.id');

        $this->respondToAttachmentItem($receiverUser, $receiverCompany, $firstItemId, 'approve');
        $this->assertSame(
            'partial',
            $this->attachmentItemHistory($requestId, 'attachment_approved', $firstItemId)->metadata['decision_scope']
        );

        $partialHistory = $this->fetchAttachmentRequestFromList($project)['history'];
        $this->assertSame([
            'request_created',
            'attachment_approved',
            'workflow_step_pending',
        ], collect($partialHistory)->pluck('action')->all());
        $this->assertSame($firstItemId, $partialHistory[1]['metadata']['item_id']);
        $this->assertSame('partial', $partialHistory[1]['metadata']['decision_scope']);
        $this->assertSame(1, (int) $partialHistory[1]['metadata']['template_step_order']);
        $this->assertSame(2, (int) $partialHistory[2]['metadata']['template_step_order']);

        $this->respondToAttachmentItem($receiverUser, $receiverCompany, $secondItemId, 'approve');
        $this->assertSame(
            'full',
            $this->attachmentItemHistory($requestId, 'attachment_approved', $secondItemId)->metadata['decision_scope']
        );

        $fullHistory = $this->fetchAttachmentRequestFromList($project)['history'];
        $this->assertSame([
            'request_created',
            'attachment_approved',
            'attachment_approved',
            'request_approved',
        ], collect($fullHistory)->pluck('action')->all());
        $this->assertSame('partial', $fullHistory[1]['metadata']['decision_scope']);
        $this->assertSame('full', $fullHistory[2]['metadata']['decision_scope']);
    }

    public function test_item_decline_history_scope_changes_from_partial_to_full_when_all_two_files_are_declined(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);
        $this->createProcedureStep($procedure, $receiverUser, 2);

        $createResponse = $this->postAttachmentRequest($project, $procedure, 2)->assertOk();
        $requestId = $createResponse->json('payload.id');
        $firstItemId = $createResponse->json('payload.items.0.id');
        $secondItemId = $createResponse->json('payload.items.1.id');

        $this->respondToAttachmentItem($receiverUser, $receiverCompany, $firstItemId, 'decline');
        $this->assertSame(
            'partial',
            $this->attachmentItemHistory($requestId, 'attachment_declined', $firstItemId)->metadata['decision_scope']
        );
        $this->assertDatabaseHas('processes', [
            'processable_id' => $requestId,
            'status' => ProcessStatus::InProgress->value,
        ]);
        $this->assertHistoryCount($requestId, 'request_declined', 0);

        $partialHistory = $this->fetchAttachmentRequestFromList($project)['history'];
        $this->assertSame([
            'request_created',
            'attachment_declined',
            'workflow_step_pending',
        ], collect($partialHistory)->pluck('action')->all());
        $this->assertSame($firstItemId, $partialHistory[1]['metadata']['item_id']);
        $this->assertSame('partial', $partialHistory[1]['metadata']['decision_scope']);
        $this->assertSame(1, (int) $partialHistory[1]['metadata']['template_step_order']);
        $this->assertSame(2, (int) $partialHistory[2]['metadata']['template_step_order']);

        $this->respondToAttachmentItem($receiverUser, $receiverCompany, $secondItemId, 'decline');
        $this->assertSame(
            'full',
            $this->attachmentItemHistory($requestId, 'attachment_declined', $secondItemId)->metadata['decision_scope']
        );

        $this->assertDatabaseHas('processes', [
            'processable_id' => $requestId,
            'status' => ProcessStatus::Failed->value,
        ]);
        $this->assertHistoryCount($requestId, 'workflow_step_rejected', 1);
        $this->assertHistoryCount($requestId, 'workflow_step_pending', 1);
        $this->assertHistoryCount($requestId, 'request_declined', 1);

        $history = $this->fetchAttachmentRequestFromList($project)['history'];
        $this->assertSame([
            'request_created',
            'attachment_declined',
            'attachment_declined',
            'request_declined',
        ], collect($history)->pluck('action')->all());
        $this->assertSame('partial', $history[1]['metadata']['decision_scope']);
        $this->assertSame('full', $history[2]['metadata']['decision_scope']);
        $this->assertFalse(collect($history)->contains(
            static fn (array $entry): bool => $entry['action'] === 'workflow_step_rejected'
        ));
        $this->assertFalse(collect($history)->contains(
            static fn (array $entry): bool => $entry['action'] === 'workflow_step_pending'
        ));
    }

    public function test_mixed_two_file_item_decisions_remain_partial_in_history(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);
        $this->createProcedureStep($procedure, $receiverUser, 2);

        $createResponse = $this->postAttachmentRequest($project, $procedure, 2)->assertOk();
        $requestId = $createResponse->json('payload.id');
        $approvedItemId = $createResponse->json('payload.items.0.id');
        $declinedItemId = $createResponse->json('payload.items.1.id');

        $this->respondToAttachmentItem($receiverUser, $receiverCompany, $approvedItemId, 'approve');
        $this->respondToAttachmentItem($receiverUser, $receiverCompany, $declinedItemId, 'decline');

        $this->assertSame(
            'partial',
            $this->attachmentItemHistory($requestId, 'attachment_approved', $approvedItemId)->metadata['decision_scope']
        );
        $this->assertSame(
            'partial',
            $this->attachmentItemHistory($requestId, 'attachment_declined', $declinedItemId)->metadata['decision_scope']
        );
        $this->assertDatabaseHas('processes', [
            'processable_id' => $requestId,
            'status' => ProcessStatus::InProgress->value,
        ]);
        $this->assertHistoryCount($requestId, 'request_declined', 0);

        $history = $this->fetchAttachmentRequestFromList($project)['history'];
        $this->assertSame([
            'request_created',
            'attachment_approved',
            'attachment_declined',
        ], collect($history)->pluck('action')->all());
        $this->assertSame(1, (int) $history[1]['metadata']['template_step_order']);
        $this->assertSame(2, (int) $history[2]['metadata']['template_step_order']);
        $this->assertSame(0, collect($history)->where('action', 'workflow_step_pending')->count());
    }

    public function test_full_item_decline_fails_a_job_role_workflow_without_advancing_it(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $this->management->forceFill(['manager_id' => $this->actor->id])->saveQuietly();
        $this->createJobRoleProcedureStep($procedure, 1);
        $this->createProcedureStep($procedure, $this->actor, 2);

        $createResponse = $this->postAttachmentRequest($project, $procedure)->assertOk();
        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');

        $this->respondToAttachmentItem($this->actor, $this->company, $itemId, 'decline');

        $process = Process::query()
            ->where('processable_id', $requestId)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->with('steps')
            ->firstOrFail();

        $this->assertSame(ProcessStatus::Failed, $process->status);
        $this->assertSame(1, $process->steps->count());
        $this->assertSame(ProcessStepStatus::Rejected, $process->steps->sole()->status);
        $this->assertHistoryCount($requestId, 'request_declined', 1);
        $this->assertHistoryCount($requestId, 'request_approved', 0);
    }

    public function test_normal_job_role_rejection_still_advances_the_workflow(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $this->management->forceFill(['manager_id' => $this->actor->id])->saveQuietly();
        $this->createJobRoleProcedureStep($procedure, 1);
        $this->createProcedureStep($procedure, $this->actor, 2);

        $requestId = $this->postAttachmentRequest($project, $procedure)
            ->assertOk()
            ->json('payload.id');

        $process = Process::query()
            ->where('processable_id', $requestId)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->firstOrFail();
        $firstStep = $process->steps()->sole();

        $this->actingAs($this->actor, 'api');
        app(ProcessWorkflowService::class)->rejectStep((string) $firstStep->id);

        $process->refresh()->load('steps');

        $this->assertSame(ProcessStatus::InProgress, $process->status);
        $this->assertSame(2, $process->steps->count());
        $this->assertSame(ProcessStepStatus::Rejected, $process->steps->firstWhere('template_step_order', 1)->status);
        $this->assertSame(ProcessStepStatus::Pending, $process->steps->firstWhere('template_step_order', 2)->status);
        $this->assertHistoryCount($requestId, 'request_declined', 0);
    }

    public function test_three_file_approval_history_scope_is_partial_until_the_final_manual_approval(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);
        $this->createProcedureStep($procedure, $receiverUser, 2);
        $this->createProcedureStep($procedure, $receiverUser, 3);

        $createResponse = $this->postAttachmentRequest($project, $procedure, 3)->assertOk();
        $requestId = $createResponse->json('payload.id');
        $firstItemId = $createResponse->json('payload.items.0.id');
        $secondItemId = $createResponse->json('payload.items.1.id');
        $thirdItemId = $createResponse->json('payload.items.2.id');

        $this->respondToAttachmentItem($receiverUser, $receiverCompany, $firstItemId, 'approve');
        $this->respondToAttachmentItem($receiverUser, $receiverCompany, $secondItemId, 'approve');
        $this->assertSame(
            'partial',
            $this->attachmentItemHistory($requestId, 'attachment_approved', $secondItemId)->metadata['decision_scope']
        );

        $this->respondToAttachmentItem($receiverUser, $receiverCompany, $thirdItemId, 'approve');
        $this->assertSame(
            'full',
            $this->attachmentItemHistory($requestId, 'attachment_approved', $thirdItemId)->metadata['decision_scope']
        );
    }

    public function test_three_file_mixed_decisions_keep_decline_history_partial(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);
        $this->createProcedureStep($procedure, $receiverUser, 2);

        $createResponse = $this->postAttachmentRequest($project, $procedure, 3)->assertOk();
        $requestId = $createResponse->json('payload.id');
        $approvedItemId = $createResponse->json('payload.items.0.id');
        $firstDeclinedItemId = $createResponse->json('payload.items.1.id');
        $secondDeclinedItemId = $createResponse->json('payload.items.2.id');

        $this->respondToAttachmentItem($receiverUser, $receiverCompany, $approvedItemId, 'approve');
        $this->respondToAttachmentItem($receiverUser, $receiverCompany, $firstDeclinedItemId, 'decline');
        $this->respondToAttachmentItem($receiverUser, $receiverCompany, $secondDeclinedItemId, 'decline');

        $this->assertSame(
            'partial',
            $this->attachmentItemHistory(
                $requestId,
                'attachment_declined',
                $secondDeclinedItemId
            )->metadata['decision_scope']
        );
    }

    public function test_legacy_item_history_without_decision_scope_is_returned_without_the_field(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $receiverUser, 1);

        $createResponse = $this->postAttachmentRequest($project, $procedure)->assertOk();
        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');

        $legacyApprovalHistoryId = $this->insertHistoricalHistory(
            requestId: $requestId,
            action: 'attachment_approved',
            description: 'Legacy attachment approval',
            userId: (string) $receiverUser->id,
            itemId: $itemId,
            metadata: ['item_id' => $itemId],
            createdAt: now(),
        );
        $legacyDeclineHistoryId = $this->insertHistoricalHistory(
            requestId: $requestId,
            action: 'attachment_declined',
            description: 'Legacy attachment decline',
            userId: (string) $receiverUser->id,
            itemId: $itemId,
            metadata: ['item_id' => $itemId],
            createdAt: now(),
        );

        $historyById = collect($this->fetchAttachmentRequestFromList($project)['history'])->keyBy('id');

        $this->assertArrayNotHasKey('decision_scope', $historyById[$legacyApprovalHistoryId]['metadata']);
        $this->assertArrayNotHasKey('decision_scope', $historyById[$legacyDeclineHistoryId]['metadata']);
    }

    private function runHistoricalAttachmentRequestHistoryMigration(): void
    {
        $migration = require database_path('migrations/2026_08_11_000000_repair_historical_attachment_request_history_data.php');

        $migration->up();
    }

    private function runHistoricalFinalApprovalStatusMigration(): void
    {
        $migration = require database_path('migrations/2026_08_17_000000_mark_historically_approved_attachment_requests.php');

        $migration->up();
    }

    private function runLegacyPendingWorkflowStatusRepairMigration(): void
    {
        $migration = require database_path(
            'migrations/2026_08_18_000000_reopen_legacy_attachment_requests_with_pending_workflows.php'
        );

        $migration->up();
    }

    private function fetchAttachmentRequestFromList(ProjectManagement $project): array
    {
        return $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests?project_id='.$project->id.'&page=1&per_page=10')
            ->assertOk()
            ->json('data.0');
    }

    private function insertHistoricalHistory(
        string $requestId,
        string $action,
        string $description,
        ?string $userId,
        ?string $itemId,
        array $metadata,
        \DateTimeInterface $createdAt,
        ?int $sortOrder = null
    ): string {
        $id = (string) Str::uuid();

        DB::table('attachment_request_history')->insert([
            'id' => $id,
            'attachment_request_id' => $requestId,
            'attachment_request_item_id' => $itemId,
            'action' => $action,
            'description' => $description,
            'user_id' => $userId,
            'metadata' => $this->historyJson($metadata),
            'dedupe_key' => null,
            'sort_order' => $sortOrder,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
        ]);

        return $id;
    }

    private function createHistoricalAttachmentItem(string $requestId, string $fileName): string
    {
        $id = (string) Str::uuid();

        DB::table('attachment_request_items')->insert([
            'id' => $id,
            'attachment_request_id' => $requestId,
            'file_name' => $fileName,
            'file_path' => null,
            'file_type' => 'application/pdf',
            'file_size' => 12 * 1024,
            'status' => AttachmentRequest::STATUS_PENDING,
            'responded_by_user_id' => null,
            'responded_at' => null,
            'response_notes' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        return $id;
    }

    private function historyCountForItem(string $requestId, string $action, string $itemId): int
    {
        return AttachmentRequestHistory::query()
            ->where('attachment_request_id', $requestId)
            ->where('action', $action)
            ->where(function ($query) use ($itemId): void {
                $query
                    ->where('attachment_request_item_id', $itemId)
                    ->orWhere('metadata->item_id', $itemId);
            })
            ->count();
    }

    private function attachmentItemHistory(
        string $requestId,
        string $action,
        string $itemId
    ): AttachmentRequestHistory {
        return AttachmentRequestHistory::query()
            ->where('attachment_request_id', $requestId)
            ->where('action', $action)
            ->where('attachment_request_item_id', $itemId)
            ->firstOrFail();
    }

    private function respondToAttachmentItem(
        User $user,
        Company $company,
        string $itemId,
        string $action
    ): void {
        $this->actingAs($user, 'api')
            ->withHeader('X-Tenant', $company->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $itemId,
                'action' => $action,
            ], ['Accept' => 'application/json'])
            ->assertOk();
    }

    private function historySnapshot(string $requestId): array
    {
        return DB::table('attachment_request_history')
            ->where('attachment_request_id', $requestId)
            ->orderBy('id')
            ->get()
            ->map(static fn ($row): array => [
                'id' => (string) $row->id,
                'attachment_request_item_id' => $row->attachment_request_item_id === null ? null : (string) $row->attachment_request_item_id,
                'action' => (string) $row->action,
                'description' => (string) $row->description,
                'user_id' => $row->user_id === null ? null : (string) $row->user_id,
                'metadata' => $row->metadata,
                'dedupe_key' => $row->dedupe_key,
                'sort_order' => $row->sort_order === null ? null : (int) $row->sort_order,
                'created_at' => (string) $row->created_at,
            ])
            ->all();
    }

    private function requestSnapshot(string $requestId): array
    {
        $row = DB::table('attachment_requests')
            ->where('id', $requestId)
            ->first();

        return [
            'status' => (string) $row->status,
            'responded_by_user_id' => $row->responded_by_user_id === null ? null : (string) $row->responded_by_user_id,
            'responded_at' => $row->responded_at === null ? null : (string) $row->responded_at,
            'updated_at' => (string) $row->updated_at,
        ];
    }

    private function processSnapshot(string $requestId): array
    {
        $row = DB::table('processes')
            ->where('processable_id', $requestId)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->first();

        return [
            'id' => (string) $row->id,
            'status' => (string) $row->status,
            'sort_order' => $row->sort_order === null ? null : (int) $row->sort_order,
            'updated_at' => (string) $row->updated_at,
        ];
    }

    private function processStepsSnapshot(string $requestId): array
    {
        $process = DB::table('processes')
            ->where('processable_id', $requestId)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->first();

        return DB::table('process_steps')
            ->where('process_id', $process->id)
            ->orderBy('id')
            ->get()
            ->map(static fn ($row): array => [
                'id' => (string) $row->id,
                'step_id' => $row->step_id === null ? null : (int) $row->step_id,
                'template_step_order' => $row->template_step_order === null ? null : (int) $row->template_step_order,
                'assigned_user_id' => (string) $row->assigned_user_id,
                'authorized_user_ids' => $row->authorized_user_ids,
                'status' => (string) $row->status,
                'action_by' => $row->action_by === null ? null : (string) $row->action_by,
                'acted_at' => $row->acted_at === null ? null : (string) $row->acted_at,
                'updated_at' => (string) $row->updated_at,
            ])
            ->all();
    }

    private function historyJson(array $metadata): string
    {
        return json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function schemaReady(): bool
    {
        return Schema::hasTable('attachment_requests')
            && Schema::hasColumn('attachment_requests', 'procedure_setting_id')
            && ! Schema::hasColumn('attachment_requests', 'receiver_company_id')
            && ! Schema::hasColumn('attachment_requests', 'attachment_type_id')
            && ! Schema::hasColumn('attachment_requests', 'attachment_sub_type_id')
            && ! Schema::hasColumn('attachment_requests', 'attachment_sub_sub_type_id')
            && Schema::hasTable('attachment_request_items')
            && Schema::hasTable('attachment_request_history')
            && Schema::hasColumn('attachment_request_history', 'dedupe_key')
            && Schema::hasColumn('attachment_request_history', 'sort_order')
            && Schema::hasTable('project_procedure_settings')
            && Schema::hasTable('folders')
            && Schema::hasTable('procedure_settings')
            && Schema::hasTable('procedure_setting_steps')
            && Schema::hasTable('procedure_setting_step_action_takers')
            && Schema::hasTable('processes')
            && Schema::hasTable('process_steps')
            && Schema::hasTable('resource_shares')
            && Schema::hasTable('work_flows')
            && Schema::hasTable('media')
            && Schema::hasTable('project_procedure_setting_receiver_companies');
    }

    private function assertHistoryCount(string $requestId, string $action, int $expected): void
    {
        $this->assertSame(
            $expected,
            AttachmentRequestHistory::query()
                ->where('attachment_request_id', $requestId)
                ->where('action', $action)
                ->count()
        );
    }

    private function workflowStepHistory(string $requestId, string $processStepId): AttachmentRequestHistory
    {
        return AttachmentRequestHistory::query()
            ->where('attachment_request_id', $requestId)
            ->where('metadata->process_step_id', $processStepId)
            ->firstOrFail();
    }

    private function assertHistoryUsersAreArrays(array $history): void
    {
        foreach ($history as $entry) {
            $this->assertIsArray($entry['user']);
        }
    }

    private function createProject(): ProjectManagement
    {
        $projectTypeId = $this->projectTypeId();

        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->withoutGlobalScopes()->forceCreate([
            'id' => (string) Str::uuid(),
            'project_type_id' => $projectTypeId,
            'sub_project_type_id' => $projectTypeId,
            'sub_sub_project_type_id' => $projectTypeId,
            'name' => 'Attachment Request Procedure Test',
            'company_id' => $this->company->id,
            'status' => 1,
            'serial_number' => 'ARP-'.Str::upper(Str::random(6)),
        ]));
    }

    private function projectTypeId(): int
    {
        return (int) ProjectType::query()->withoutGlobalScopes()->firstOrCreate(
            [
                'name' => 'Attachment Request Procedure Test Type',
                'company_id' => $this->company->id,
            ],
            [
                'is_created' => true,
                'is_have_schema' => false,
                'is_active' => true,
            ],
        )->id;
    }

    private function createProjectProcedure(
        ProjectManagement $project,
        array $receiverCompanyIds = []
    ): ProjectProcedureSetting
    {
        $workFlow = WorkFlow::query()->withoutGlobalScopes()->firstOrCreate([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'name' => 'project_'.$project->id,
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
        ]);

        $parent = ProcedureSetting::query()->withoutGlobalScopes()->firstOrCreate([
            'company_id' => $this->company->id,
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
            'work_flow_id' => $workFlow->id,
            'parent_id' => null,
        ], [
            'name' => 'Project Procedures',
            'execute_type' => 'sequence',
            'is_active' => true,
        ]);

        $procedureSetting = ProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'name' => 'Document Approval',
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
            'execute_type' => 'sequence',
            'is_active' => true,
            'work_flow_id' => $workFlow->id,
            'parent_id' => $parent->id,
            'sort_order' => 1,
        ]);

        $attachmentType = $this->createFolder($project, 'Project Docs');
        $attachmentSubType = $this->createFolder($project, 'Design Docs', $attachmentType->id);
        $attachmentSubSubType = $this->createFolder($project, 'Issued For Approval', $attachmentSubType->id);

        $projectProcedure = ProjectProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'procedure_setting_id' => $procedureSetting->id,
            'attachment_type_id' => $attachmentType->id,
            'attachment_sub_type_id' => $attachmentSubType->id,
            'attachment_sub_sub_type_id' => $attachmentSubSubType->id,
            'used_in_document_cycle' => true,
        ]);

        if ($receiverCompanyIds !== []) {
            $projectProcedure->receiverCompanies()->sync($receiverCompanyIds);
        }

        return $projectProcedure->refresh();
    }

    private function createFolder(ProjectManagement $project, string $name, ?string $parentId = null): Folder
    {
        return Folder::query()->withoutGlobalScopes()->create([
            'name' => $name,
            'parent_id' => $parentId,
            'project_id' => $project->id,
            'company_id' => $this->company->id,
            'access_type' => 'private',
            'status' => 1,
        ]);
    }

    private function createCompany(array $overrides = []): Company
    {
        return Company::withoutEvents(fn () => Company::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Attachment Receiver Company'],
            'user_name' => 'attachment_receiver_'.Str::lower(Str::random(6)),
            'email' => 'attachment-receiver-'.Str::lower(Str::random(6)).'@example.test',
            'phone' => '01000000000',
            'country_id' => $this->country->id,
            'company_type_id' => (string) Str::uuid(),
            'company_field_id' => (string) Str::uuid(),
            'registration_type_id' => (string) Str::uuid(),
            'general_manager_id' => (string) Str::uuid(),
            'is_active' => 1,
            'complete_data' => 1,
            'serial_no' => 'ATT-REC-'.Str::upper(Str::random(6)),
        ], $overrides)));
    }

    private function createAcceptedShare(ProjectManagement $project, Company $receiverCompany): ResourceShare
    {
        return ResourceShare::query()->create([
            'id' => (string) Str::uuid(),
            'shareable_type' => ProjectManagement::class,
            'shareable_id' => $project->id,
            'owner_company_id' => $this->company->id,
            'shared_with_company_id' => $receiverCompany->id,
            'status' => 'accepted',
            'schema_ids' => [1, 2],
            'shared_by_user_id' => $this->actor->id,
            'responded_by_user_id' => $this->actor->id,
            'responded_at' => now(),
        ]);
    }

    private function postAttachmentRequest(
        ProjectManagement $project,
        ProjectProcedureSetting $procedure,
        int $attachmentCount = 1
    ) {
        $attachments = collect(range(1, $attachmentCount))
            ->map(static fn (int $index): UploadedFile => UploadedFile::fake()->create(
                $attachmentCount === 1 ? 'workflow-file.pdf' : "workflow-file-{$index}.pdf",
                12,
                'application/pdf'
            ))
            ->all();

        return $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests', [
                'name' => 'Workflow Attachment Files',
                'date' => '2026-07-21',
                'project_id' => $project->id,
                'procedure_setting_id' => $procedure->procedure_setting_id,
                'attachments' => $attachments,
            ], ['Accept' => 'application/json']);
    }

    private function createProcedureStep(
        ProjectProcedureSetting $procedure,
        User $user,
        int $order
    ): ProcedureSettingStep {
        $step = ProcedureSettingStep::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'procedure_setting_id' => $procedure->procedure_setting_id,
            'project_id' => $procedure->project_id,
            'name' => 'Attachment Workflow Step '.$order,
            'forms' => 'approve',
            'is_approve' => true,
            'step_order' => $order,
            'action_taker_type' => 'specific_user',
        ]);

        ProcedureSettingStepActionTaker::query()->create([
            'procedure_setting_step_id' => $step->id,
            'user_id' => $user->id,
            'company_id' => $this->company->id,
        ]);

        return $step;
    }

    private function createJobRoleProcedureStep(
        ProjectProcedureSetting $procedure,
        int $order
    ): ProcedureSettingStep {
        return ProcedureSettingStep::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'procedure_setting_id' => $procedure->procedure_setting_id,
            'project_id' => $procedure->project_id,
            'name' => 'Attachment Job Role Workflow Step '.$order,
            'forms' => 'approve',
            'is_approve' => true,
            'step_order' => $order,
            'action_taker_type' => 'specific_procedures',
            'action_taker_specific_procedure_type' => ['job_role'],
            'action_taker_specific_procedure_id' => ['1'],
        ]);
    }

    private function createReceiverCompanyProcedureStep(
        ProjectProcedureSetting $procedure,
        int $order,
        array $receiverCompanyIds
    ): ProcedureSettingStep {
        return ProcedureSettingStep::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'procedure_setting_id' => $procedure->procedure_setting_id,
            'project_id' => $procedure->project_id,
            'name' => 'Receiver Company Workflow Step '.$order,
            'forms' => 'approve',
            'is_approve' => true,
            'step_order' => $order,
            'action_taker_type' => 'receiver_company',
            'receiver_company_ids' => $receiverCompanyIds,
        ]);
    }
}
