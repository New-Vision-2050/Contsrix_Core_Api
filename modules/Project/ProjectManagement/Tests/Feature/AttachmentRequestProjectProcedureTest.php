<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\ArchiveLibrary\File\Models\File as ArchiveFile;
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
use Modules\Project\ProjectManagement\Services\AttachmentArchiveDeliveryService;
use Modules\Project\ProjectManagement\Services\AttachmentRequestService;
use Modules\Project\ProjectManagement\Services\ProjectProcedureService;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\Shared\ResourceShare\Models\ResourceShare;
use Modules\Shared\Media\Models\CustomMedia;
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

    public function test_attachment_request_list_filters_by_procedure_setting_and_receiver_companies(): void
    {
        $project = $this->createProject();
        $matchingReceiverCompany = $this->createCompany();
        $otherReceiverCompany = $this->createCompany();
        $matchingProcedure = $this->createProjectProcedure($project, [$matchingReceiverCompany->id]);
        $otherProcedure = $this->createProjectProcedure($project, [$otherReceiverCompany->id]);

        $matchingRequestId = $this->postAttachmentRequest($project, $matchingProcedure)
            ->assertOk()
            ->json('payload.id');
        $otherRequestId = $this->postAttachmentRequest($project, $otherProcedure)
            ->assertOk()
            ->json('payload.id');

        $procedureSettingIds = collect($this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests?procedure_setting_id='.$matchingProcedure->procedure_setting_id)
            ->assertOk()
            ->json('data'))->pluck('id')->all();

        $this->assertContains($matchingRequestId, $procedureSettingIds);
        $this->assertNotContains($otherRequestId, $procedureSettingIds);

        $receiverCompanyIds = collect($this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests?receiver_company_ids[]='.$matchingReceiverCompany->id)
            ->assertOk()
            ->json('data'))->pluck('id')->all();

        $this->assertContains($matchingRequestId, $receiverCompanyIds);
        $this->assertNotContains($otherRequestId, $receiverCompanyIds);
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
            Carbon::setTestNow($timelineStart->copy()->addMinute());
            app(ProcessWorkflowService::class)->autoApproveStep((string) $autoStep->id);

            $this->assertDatabaseHas('process_steps', [
                'id' => $autoStep->id,
                'template_step_order' => 1,
                'status' => ProcessStepStatus::Approved->value,
                'action_by' => null,
            ]);

            Carbon::setTestNow($timelineStart->copy()->addMinutes(2));
            $this->actingAs($firstManualUser, 'api')
                ->withHeader('X-Tenant', $receiverCompany->id)
                ->post("/api/v1/projects/attachment-requests/{$requestId}/approve", [], ['Accept' => 'application/json'])
                ->assertOk()
                ->assertJsonPath('payload.status', AttachmentRequest::STATUS_PENDING);

            Carbon::setTestNow($timelineStart->copy()->addMinutes(3));
            $this->actingAs($secondManualUser, 'api')
                ->withHeader('X-Tenant', $receiverCompany->id)
                ->post("/api/v1/projects/attachment-requests/{$requestId}/approve", [], ['Accept' => 'application/json'])
                ->assertOk()
                ->assertJsonPath('payload.status', AttachmentRequest::STATUS_APPROVED);
        } finally {
            Carbon::setTestNow();
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

    public function test_document_is_delivered_to_archive_only_after_final_workflow_approval(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $firstReceiverUser = User::factory()->create(['company_id' => $this->company->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $this->company->id]);
        $thirdReceiverUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->createProcedureStep($procedure, $firstReceiverUser, 1);
        $this->createProcedureStep($procedure, $secondReceiverUser, 2);
        $this->createProcedureStep($procedure, $thirdReceiverUser, 3);

        $createResponse = $this->postAttachmentRequest($project, $procedure)->assertOk();
        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');

        tenancy()->initialize($this->company);
        $this->createProjectArchiveRootFolder($project);
        $sourceItem = AttachmentRequestItem::query()->findOrFail($itemId);
        $sourceMedia = $sourceItem->getFirstMedia('attachments');
        $this->assertNotNull($sourceMedia);

        $archiveFiles = fn () => ArchiveFile::query()
            ->withoutTenancy()
            ->where('company_id', $this->company->id)
            ->where('project_id', $project->id)
            ->where('folder_id', $procedure->attachment_sub_sub_type_id);

        $this->assertSame(0, $archiveFiles()->count());

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $itemId,
                'action' => 'approve',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        tenancy()->initialize($this->company);
        $this->assertSame(0, $archiveFiles()->count());

        $this->actingAs($secondReceiverUser, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $itemId,
                'action' => 'approve',
                'notes' => 'Approved with notes',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        tenancy()->initialize($this->company);
        $this->assertSame(0, $archiveFiles()->count());

        $this->actingAs($thirdReceiverUser, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $itemId,
                'action' => 'approve',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        tenancy()->initialize($this->company);
        $completedProcess = Process::query()
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->where('processable_id', $requestId)
            ->firstOrFail();
        $this->assertSame(ProcessStatus::Completed, $completedProcess->status);
        $this->assertTrue(AttachmentRequest::query()->findOrFail($requestId)->isApproved());
        $this->assertSame(1, $archiveFiles()->count());
        $firstArchiveFile = $archiveFiles()->firstOrFail();
        $this->assertSame(AttachmentRequestItem::class, $firstArchiveFile->source_model_type);
        $this->assertSame($itemId, $firstArchiveFile->source_model_id);
        $this->assertSame($sourceMedia->id, $firstArchiveFile->source_media_id);
        $this->assertSame(1, CustomMedia::query()
            ->where('model_type', ArchiveFile::class)
            ->where('model_id', $firstArchiveFile->id)
            ->where('collection_name', 'upload')
            ->count());
        $firstArchiveMedia = CustomMedia::query()
            ->where('model_type', ArchiveFile::class)
            ->where('model_id', $firstArchiveFile->id)
            ->where('collection_name', 'upload')
            ->firstOrFail();
        $this->assertSame($sourceMedia->disk, $firstArchiveMedia->disk);
        $this->assertSame($sourceMedia->file_name, $firstArchiveMedia->file_name);
        $this->assertSame(
            $sourceMedia->getCustomProperty('file_path'),
            $firstArchiveMedia->getCustomProperty('file_path')
        );

        app(AttachmentRequestService::class)->completeWorkflowApproval(
            AttachmentRequest::query()->findOrFail($requestId)
        );
        app(AttachmentArchiveDeliveryService::class)->deliverAttachmentRequestItem(
            AttachmentRequestItem::query()
                ->with('attachmentRequest.projectProcedureSetting')
                ->findOrFail($itemId)
        );
        $this->assertSame(1, $archiveFiles()->count());
        $this->assertSame(1, CustomMedia::query()
            ->where('model_type', ArchiveFile::class)
            ->whereIn('model_id', $archiveFiles()->pluck('id'))
            ->where('collection_name', 'upload')
            ->count());

        $newUploadResponse = $this->postAttachmentRequest($project, $procedure)->assertOk();
        $newUploadItemId = $newUploadResponse->json('payload.items.0.id');

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $newUploadItemId,
                'action' => 'approve',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        tenancy()->initialize($this->company);
        $this->assertSame(1, $archiveFiles()->count());

        $this->actingAs($secondReceiverUser, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $newUploadItemId,
                'action' => 'approve',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        tenancy()->initialize($this->company);
        $this->assertSame(1, $archiveFiles()->count());

        $this->actingAs($thirdReceiverUser, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $newUploadItemId,
                'action' => 'approve',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        tenancy()->initialize($this->company);
        $this->assertSame(2, $archiveFiles()->count());
        $this->assertSame(1, $archiveFiles()
            ->where('source_model_type', AttachmentRequestItem::class)
            ->where('source_model_id', $newUploadItemId)
            ->count());
    }

    public function test_rejected_attachment_request_is_not_delivered_to_the_archive_library(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $firstReceiverUser = User::factory()->create(['company_id' => $this->company->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->createProcedureStep($procedure, $firstReceiverUser, 1);
        $this->createProcedureStep($procedure, $secondReceiverUser, 2);

        $createResponse = $this->postAttachmentRequest($project, $procedure)->assertOk();
        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');

        tenancy()->initialize($this->company);
        $this->createProjectArchiveRootFolder($project);

        $archiveFiles = fn () => ArchiveFile::query()
            ->withoutTenancy()
            ->where('company_id', $this->company->id)
            ->where('project_id', $project->id)
            ->where('folder_id', $procedure->attachment_sub_sub_type_id);

        $this->assertSame(0, $archiveFiles()->count());

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $itemId,
                'action' => 'approve',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        tenancy()->initialize($this->company);
        $this->assertSame(0, $archiveFiles()->count());

        $this->actingAs($secondReceiverUser, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests/items/respond', [
                'item_id' => $itemId,
                'action' => 'decline',
                'notes' => 'Rejected before final approval',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        tenancy()->initialize($this->company);
        $failedProcess = Process::query()
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->where('processable_id', $requestId)
            ->firstOrFail();
        $this->assertSame(ProcessStatus::Failed, $failedProcess->status);
        $this->assertSame(0, $archiveFiles()->count());
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

    public function test_visible_receiver_can_replace_attachment_media(): void
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
        $replacementName = 'receiver-replacement.pdf';

        $this->actingAs($receiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post('/api/v1/projects/attachment-requests/items/replace-media', [
                'item_id' => $itemId,
                'new_file' => UploadedFile::fake()->create($replacementName, 16, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.items.0.file_name', $replacementName);

        $replacedItem = $beforeReplacement->fresh();

        $this->assertSame($replacementName, $replacedItem->file_name);
        $this->assertSame('application/pdf', $replacedItem->file_type);
        $this->assertSame(1, $replacedItem->getMedia('attachments')->count());
        $this->assertHistoryCount($requestId, 'media_replaced', 1);
    }

    public function test_replace_media_via_resumable_chunked_upload(): void
    {
        Storage::fake('local');

        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);

        $createResponse = $this->postAttachmentRequest($project, $procedure)
            ->assertOk();

        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');

        $content = str_repeat('A', 20) . str_repeat('B', 20);
        $uploadId = $this->completeChunkedUpload(
            $this->company->id,
            'chunked-replacement.pdf',
            'application/pdf',
            [substr($content, 0, 20), substr($content, 20, 20)]
        );

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests/items/replace-media', [
                'item_id' => $itemId,
                'upload_id' => $uploadId,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.items.0.file_name', 'chunked-replacement.pdf');

        $replacedItem = AttachmentRequestItem::query()->findOrFail($itemId);
        $this->assertSame('chunked-replacement.pdf', $replacedItem->file_name);
        $this->assertSame(40, $replacedItem->file_size);
        $this->assertHistoryCount($requestId, 'media_replaced', 1);
    }

    public function test_create_attachment_request_via_resumable_upload_token(): void
    {
        Storage::fake('local');

        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);

        $content = str_repeat('X', 15) . str_repeat('Y', 15);
        $uploadId = $this->completeChunkedUpload(
            $this->company->id,
            'chunked-create.pdf',
            'application/pdf',
            [substr($content, 0, 15), substr($content, 15, 15)]
        );

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests', [
                'name' => 'Resumable Upload Request',
                'date' => '2026-07-21',
                'project_id' => $project->id,
                'procedure_setting_id' => $procedure->procedure_setting_id,
                'attachment_upload_ids' => [$uploadId],
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.items.0.file_name', 'chunked-create.pdf')
            ->assertJsonPath('payload.items.0.file_size', 30);
    }

    /**
     * Drives the init -> chunk -> complete resumable upload flow via the HTTP
     * API and returns the resulting upload_id token.
     *
     * @param  list<string>  $chunkContents
     */
    private function completeChunkedUpload(
        string $companyId,
        string $fileName,
        string $mimeType,
        array $chunkContents
    ): string {
        $totalSize = array_sum(array_map('strlen', $chunkContents));

        $initResponse = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $companyId)
            ->post('/api/v1/projects/attachment-requests/uploads/init', [
                'file_name' => $fileName,
                'file_size' => $totalSize,
                'total_chunks' => count($chunkContents),
                'mime_type' => $mimeType,
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $uploadId = $initResponse->json('payload.upload_id');

        foreach ($chunkContents as $index => $chunkContent) {
            $tmpPath = tempnam(sys_get_temp_dir(), 'chunk');
            file_put_contents($tmpPath, $chunkContent);
            $chunkFile = new UploadedFile($tmpPath, "chunk_{$index}", $mimeType, null, true);

            $this->actingAs($this->actor, 'api')
                ->withHeader('X-Tenant', $companyId)
                ->post("/api/v1/projects/attachment-requests/uploads/{$uploadId}/chunk", [
                    'chunk_index' => $index,
                    'chunk' => $chunkFile,
                ], ['Accept' => 'application/json'])
                ->assertOk();
        }

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $companyId)
            ->post("/api/v1/projects/attachment-requests/uploads/{$uploadId}/complete", [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.upload_id', $uploadId);

        return $uploadId;
    }

    public function test_unrelated_company_cannot_replace_attachment_media(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $unrelatedCompany = $this->createCompany();
        $unrelatedUser = User::factory()->create(['company_id' => $unrelatedCompany->id]);

        $createResponse = $this->postAttachmentRequest($project, $procedure)
            ->assertOk();

        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');
        $beforeReplacement = AttachmentRequestItem::query()->findOrFail($itemId);
        $mediaCount = $beforeReplacement->getMedia('attachments')->count();

        $this->actingAs($unrelatedUser, 'api')
            ->withHeader('X-Tenant', $unrelatedCompany->id)
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

        $this->runLegacyAttachmentApprovalHistoryRepairMigration();

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
            ->filter(static fn (AttachmentRequestHistory $history): bool => (int) ($history->metadata['template_step_order'] ?? 0) === (int) $thirdStep->template_step_order
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

    public function test_legacy_attachment_approval_history_migration_removes_displayed_stale_pending_history(): void
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

        $createResponse = $this->postAttachmentRequest($project, $procedure)->assertOk();
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
        $attachmentApproval = AttachmentRequestHistory::query()
            ->where('attachment_request_id', $requestId)
            ->where('action', 'attachment_approved')
            ->firstOrFail();
        $legacyApprovalMetadata = $attachmentApproval->metadata;
        unset(
            $legacyApprovalMetadata['process_id'],
            $legacyApprovalMetadata['process_sort_order'],
            $legacyApprovalMetadata['step_id'],
            $legacyApprovalMetadata['template_step_order'],
            $legacyApprovalMetadata['assigned_user_id'],
            $legacyApprovalMetadata['authorized_user_ids'],
            $legacyApprovalMetadata['acted_at'],
            $legacyApprovalMetadata['is_auto_approved'],
        );

        // Preserve the genuine item-approval actor, timestamp, and ProcessStep
        // reference while restoring the incomplete legacy workflow metadata that
        // made this approval appear after the future Step 3 pending snapshot.
        DB::table('attachment_request_history')
            ->where('id', $attachmentApproval->id)
            ->update([
                'metadata' => $this->historyJson($legacyApprovalMetadata),
                'sort_order' => 100000 + ($processSortOrder * 1000) + (int) $thirdStep->template_step_order + 1,
            ]);

        $stalePendingHistoryId = $this->insertHistoricalHistory(
            requestId: $requestId,
            action: 'workflow_step_pending',
            description: 'Legacy stale pending step two',
            userId: null,
            itemId: null,
            metadata: [
                // Legacy rows can link the step but lack the process/stage metadata
                // used by the presenter to collapse a completed item decision.
                'process_step_id' => (string) $secondStep->id,
                'status' => ProcessStepStatus::Pending->value,
            ],
            createdAt: $attachmentApproval->created_at->copy()->subSecond(),
            sortOrder: 100000 + ($processSortOrder * 1000) + (int) $secondStep->template_step_order,
        );

        DB::table('attachment_requests')
            ->where('id', $requestId)
            ->update(['status' => AttachmentRequest::STATUS_APPROVED]);

        $beforeHistory = $this->fetchAttachmentRequestFromList($project)['history'];

        $this->assertSame([
            'request_created',
            'workflow_step_approved',
            'workflow_step_pending',
            'workflow_step_pending',
            'attachment_approved',
        ], collect($beforeHistory)->pluck('action')->all());

        $approvalBefore = DB::table('attachment_request_history')
            ->where('id', $attachmentApproval->id)
            ->firstOrFail();

        $this->runLegacyAttachmentApprovalHistoryRepairMigration();

        $this->assertDatabaseMissing('attachment_request_history', ['id' => $stalePendingHistoryId]);
        $this->assertDatabaseHas('attachment_requests', [
            'id' => $requestId,
            'status' => AttachmentRequest::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('processes', [
            'id' => $process->id,
            'status' => ProcessStatus::InProgress->value,
        ]);
        $this->assertDatabaseHas('process_steps', [
            'id' => $secondStep->id,
            'status' => ProcessStepStatus::Approved->value,
            'action_by' => $secondReceiverUser->id,
        ]);
        $this->assertDatabaseHas('process_steps', [
            'id' => $thirdStep->id,
            'status' => ProcessStepStatus::Pending->value,
        ]);

        $pendingHistory = AttachmentRequestHistory::query()
            ->where('attachment_request_id', $requestId)
            ->where('action', 'workflow_step_pending')
            ->get();

        $this->assertCount(1, $pendingHistory);
        $this->assertSame(
            (int) $thirdStep->template_step_order,
            (int) $pendingHistory->first()->metadata['template_step_order']
        );

        $approvalAfter = DB::table('attachment_request_history')
            ->where('id', $attachmentApproval->id)
            ->firstOrFail();
        $approvalMetadataAfter = json_decode((string) $approvalAfter->metadata, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame((string) $approvalBefore->user_id, (string) $approvalAfter->user_id);
        $this->assertSame((string) $approvalBefore->created_at, (string) $approvalAfter->created_at);
        $this->assertSame((string) $process->id, (string) $approvalMetadataAfter['process_id']);
        $this->assertSame((int) $secondStep->template_step_order, (int) $approvalMetadataAfter['template_step_order']);
        $this->assertSame(
            100000 + ($processSortOrder * 1000) + (int) $secondStep->template_step_order,
            (int) $approvalAfter->sort_order
        );

        $afterHistory = $this->fetchAttachmentRequestFromList($project)['history'];

        $this->assertSame([
            'request_created',
            'workflow_step_approved',
            'attachment_approved',
            'workflow_step_pending',
        ], collect($afterHistory)->pluck('action')->all());
        $this->assertSame((string) $secondReceiverUser->id, $afterHistory[2]['user'][0]['id']);
        $this->assertSame(
            (int) $thirdStep->template_step_order,
            (int) $afterHistory[3]['metadata']['template_step_order']
        );

        $historyAfterFirstRun = $this->historySnapshot($requestId);
        $requestAfterFirstRun = $this->requestSnapshot($requestId);
        $processAfterFirstRun = $this->processSnapshot($requestId);
        $stepsAfterFirstRun = $this->processStepsSnapshot($requestId);

        $this->runLegacyAttachmentApprovalHistoryRepairMigration();

        $this->assertSame($historyAfterFirstRun, $this->historySnapshot($requestId));
        $this->assertSame($requestAfterFirstRun, $this->requestSnapshot($requestId));
        $this->assertSame($processAfterFirstRun, $this->processSnapshot($requestId));
        $this->assertSame($stepsAfterFirstRun, $this->processStepsSnapshot($requestId));
    }

    public function test_legacy_attachment_approval_history_migration_repairs_missing_next_step_case_a(): void
    {
        $legacy = $this->recreateMissingSequentialAdvanceLegacyCase(stepCount: 3);
        $requestId = $legacy['request_id'];
        $before = $this->legacyWorkflowAuditSnapshot($requestId);

        $this->assertSame([
            ['action' => 'request_created', 'template_step_order' => null],
            ['action' => 'workflow_step_approved', 'template_step_order' => 1],
            ['action' => 'workflow_step_pending', 'template_step_order' => 2],
            ['action' => 'workflow_step_pending', 'template_step_order' => 3],
            ['action' => 'attachment_approved', 'template_step_order' => null],
        ], $before['history']);
        $this->assertSame([
            ['template_step_order' => 1, 'status' => 'approved'],
            ['template_step_order' => 2, 'status' => 'pending'],
        ], $before['process_steps']);
        $this->assertSame(AttachmentRequest::STATUS_APPROVED, $before['request_status']);
        $this->assertSame(ProcessStatus::InProgress->value, $before['process_status']);
        $this->assertDatabaseHas('attachment_request_items', [
            'id' => $legacy['item_id'],
            'status' => AttachmentRequest::STATUS_APPROVED,
            'responded_by_user_id' => $legacy['second_user_id'],
        ]);
        $this->assertDatabaseHas('process_steps', [
            'id' => $legacy['second_step_id'],
            'assigned_user_id' => $legacy['second_user_id'],
            'status' => ProcessStepStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('attachment_request_history', [
            'id' => $legacy['approval_history_id'],
            'action' => 'attachment_approved',
            'user_id' => $legacy['second_user_id'],
        ]);

        // The fixture is complete. The repair itself runs exactly this migration.
        $this->runLegacyAttachmentApprovalHistoryRepairMigration();

        $after = $this->legacyWorkflowAuditSnapshot($requestId);

        $this->assertSame([
            ['action' => 'request_created', 'template_step_order' => null],
            ['action' => 'workflow_step_approved', 'template_step_order' => 1],
            ['action' => 'attachment_approved', 'template_step_order' => 2],
            ['action' => 'workflow_step_pending', 'template_step_order' => 3],
        ], $after['history']);
        $this->assertSame([
            ['template_step_order' => 1, 'status' => 'approved'],
            ['template_step_order' => 2, 'status' => 'approved'],
            ['template_step_order' => 3, 'status' => 'pending'],
        ], $after['process_steps']);
        $this->assertSame(AttachmentRequest::STATUS_PENDING, $after['request_status']);
        $this->assertSame(ProcessStatus::InProgress->value, $after['process_status']);

        $secondStep = DB::table('process_steps')->find($legacy['second_step_id']);
        $thirdStep = DB::table('process_steps')
            ->where('process_id', $legacy['process_id'])
            ->where('template_step_order', 3)
            ->firstOrFail();
        $approval = DB::table('attachment_request_history')->find($legacy['approval_history_id']);
        $thirdPendingHistory = DB::table('attachment_request_history')->find($legacy['third_pending_history_id']);

        $this->assertSame((string) $legacy['second_user_id'], (string) $secondStep->action_by);
        $this->assertSame((string) $legacy['item_responded_at'], (string) $secondStep->acted_at);
        $this->assertSame((string) $legacy['third_pending_history_id'], (string) $thirdPendingHistory->id);
        $this->assertSame((string) $thirdStep->id, (string) $this->historyMetadata($thirdPendingHistory)['process_step_id']);
        $this->assertDatabaseMissing('attachment_request_history', ['id' => $legacy['stale_pending_history_id']]);

        $approvalMetadata = $this->historyMetadata($approval);
        $this->assertSame((string) $legacy['process_id'], (string) $approvalMetadata['process_id']);
        $this->assertSame((string) $legacy['second_step_id'], (string) $approvalMetadata['process_step_id']);
        $this->assertSame((int) $legacy['second_template_step_id'], (int) $approvalMetadata['step_id']);
        $this->assertSame(2, (int) $approvalMetadata['template_step_order']);
        $this->assertSame(
            100000 + ($legacy['process_sort_order'] * 1000) + 2,
            (int) $approval->sort_order
        );
    }

    public function test_legacy_attachment_approval_history_migration_completes_final_step_case_b(): void
    {
        $legacy = $this->recreateMissingSequentialAdvanceLegacyCase(stepCount: 2);
        $requestId = $legacy['request_id'];
        $before = $this->legacyWorkflowAuditSnapshot($requestId);

        $this->assertSame([
            ['action' => 'request_created', 'template_step_order' => null],
            ['action' => 'workflow_step_approved', 'template_step_order' => 1],
            ['action' => 'workflow_step_pending', 'template_step_order' => 2],
            ['action' => 'attachment_approved', 'template_step_order' => null],
        ], $before['history']);
        $this->assertSame([
            ['template_step_order' => 1, 'status' => 'approved'],
            ['template_step_order' => 2, 'status' => 'pending'],
        ], $before['process_steps']);
        $this->assertSame(AttachmentRequest::STATUS_PENDING, $before['request_status']);
        $this->assertSame(ProcessStatus::InProgress->value, $before['process_status']);
        $this->assertDatabaseHas('attachment_request_items', [
            'id' => $legacy['item_id'],
            'status' => AttachmentRequest::STATUS_APPROVED,
            'responded_by_user_id' => $legacy['second_user_id'],
        ]);
        $this->assertDatabaseHas('process_steps', [
            'id' => $legacy['second_step_id'],
            'assigned_user_id' => $legacy['second_user_id'],
            'status' => ProcessStepStatus::Pending->value,
        ]);

        $this->runLegacyAttachmentApprovalHistoryRepairMigration();

        $after = $this->legacyWorkflowAuditSnapshot($requestId);

        $this->assertSame([
            ['action' => 'request_created', 'template_step_order' => null],
            ['action' => 'workflow_step_approved', 'template_step_order' => 1],
            ['action' => 'attachment_approved', 'template_step_order' => 2],
        ], $after['history']);
        $this->assertSame([
            ['template_step_order' => 1, 'status' => 'approved'],
            ['template_step_order' => 2, 'status' => 'approved'],
        ], $after['process_steps']);
        $this->assertSame(AttachmentRequest::STATUS_APPROVED, $after['request_status']);
        $this->assertSame(ProcessStatus::Completed->value, $after['process_status']);
        $this->assertDatabaseMissing('attachment_request_history', ['id' => $legacy['stale_pending_history_id']]);
        $this->assertSame(2, DB::table('process_steps')
            ->where('process_id', $legacy['process_id'])
            ->count());
    }

    public function test_explicit_attachment_request_workflow_repair_migration_repairs_group_a_only_from_the_allowlist(): void
    {
        $legacy = $this->reproduceSuppliedLegacyAttachmentRequest('257');
        $requestId = $legacy['request_id'];
        $before = $this->suppliedLegacyAudit($requestId);
        $approvalBefore = $before['history']['a279eddd-97d7-4976-8c68-3d67096ee2f8'];
        $thirdPendingBefore = $before['history']['a279a043-86be-4bf8-96f3-7ccfb7a74451'];

        $this->runExplicitAttachmentRequestWorkflowRepairMigration();

        $after = $this->suppliedLegacyAudit($requestId);

        $this->assertSame([
            ['action' => 'request_created', 'template_step_order' => null],
            ['action' => 'workflow_step_approved', 'template_step_order' => 1],
            ['action' => 'attachment_approved', 'template_step_order' => 2],
            ['action' => 'workflow_step_pending', 'template_step_order' => 3],
        ], $this->sourceHistorySummary($after['history']));
        $this->assertSame([
            ['step_id' => 43, 'template_step_order' => 1, 'status' => 'approved'],
            ['step_id' => 44, 'template_step_order' => 2, 'status' => 'approved'],
            ['step_id' => 45, 'template_step_order' => 3, 'status' => 'pending'],
        ], $this->sourceStepSummary($after['process_steps']));
        $this->assertSame(AttachmentRequest::STATUS_PENDING, $after['request']['status']);
        $this->assertSame(ProcessStatus::InProgress->value, $after['process']['status']);
        $this->assertArrayNotHasKey('a279a043-8532-47c0-bb54-6b284342f27a', $after['history']);

        $secondStep = $after['process_steps']['a279e89e-c0a3-4654-8bdc-1d3374ce109b'];
        $thirdStep = collect($after['process_steps'])->firstWhere('template_step_order', 3);
        $approvalAfter = $after['history']['a279eddd-97d7-4976-8c68-3d67096ee2f8'];
        $thirdPendingAfter = $after['history']['a279a043-86be-4bf8-96f3-7ccfb7a74451'];

        $this->assertSame($approvalBefore['user_id'], $approvalAfter['user_id']);
        $this->assertSame($approvalBefore['created_at'], $approvalAfter['created_at']);
        $this->assertSame('2026-08-11 10:54:45', $secondStep['acted_at']);
        $this->assertSame('2026-08-11T10:54:45+00:00', $approvalAfter['metadata']['acted_at']);
        $this->assertSame((string) $secondStep['id'], $approvalAfter['metadata']['process_step_id']);
        $this->assertSame((string) $thirdStep['id'], $thirdPendingAfter['metadata']['process_step_id']);
        $this->assertSame($thirdPendingBefore['user_id'], $thirdPendingAfter['user_id']);
        $this->assertSame($thirdPendingBefore['created_at'], $thirdPendingAfter['created_at']);

        $historyAfterFirstRun = $this->historySnapshot($requestId);
        $requestAfterFirstRun = $this->requestSnapshot($requestId);
        $processAfterFirstRun = $this->processSnapshot($requestId);
        $stepsAfterFirstRun = $this->processStepsSnapshot($requestId);

        $this->runExplicitAttachmentRequestWorkflowRepairMigration();

        $this->assertSame($historyAfterFirstRun, $this->historySnapshot($requestId));
        $this->assertSame($requestAfterFirstRun, $this->requestSnapshot($requestId));
        $this->assertSame($processAfterFirstRun, $this->processSnapshot($requestId));
        $this->assertSame($stepsAfterFirstRun, $this->processStepsSnapshot($requestId));
    }

    public function test_explicit_attachment_request_workflow_repair_migration_repairs_group_b_without_creating_step_three(): void
    {
        $legacy = $this->reproduceSuppliedLegacyAttachmentRequest('246');
        $requestId = $legacy['request_id'];
        $before = $this->suppliedLegacyAudit($requestId);
        $approvalBefore = $before['history']['a27a0148-67be-4602-a4cb-f52a9fa63513'];

        $this->runExplicitAttachmentRequestWorkflowRepairMigration();

        $after = $this->suppliedLegacyAudit($requestId);

        $this->assertSame([
            ['action' => 'request_created', 'template_step_order' => null],
            ['action' => 'workflow_step_approved', 'template_step_order' => 1],
            ['action' => 'attachment_approved', 'template_step_order' => 2],
        ], $this->sourceHistorySummary($after['history']));
        $this->assertSame([
            ['step_id' => 43, 'template_step_order' => 1, 'status' => 'approved'],
            ['step_id' => 44, 'template_step_order' => 2, 'status' => 'approved'],
        ], $this->sourceStepSummary($after['process_steps']));
        $this->assertSame(AttachmentRequest::STATUS_APPROVED, $after['request']['status']);
        $this->assertSame(ProcessStatus::Completed->value, $after['process']['status']);
        $this->assertArrayNotHasKey('a279f212-a67d-4ce9-bf2d-4d295e3967bb', $after['history']);
        $this->assertSame(2, count($after['process_steps']));

        $secondStep = $after['process_steps']['a279f212-5a6e-4d47-907d-fd50a2ea4e28'];
        $approvalAfter = $after['history']['a27a0148-67be-4602-a4cb-f52a9fa63513'];
        $this->assertSame($approvalBefore['user_id'], $approvalAfter['user_id']);
        $this->assertSame($approvalBefore['created_at'], $approvalAfter['created_at']);
        $this->assertSame('2026-08-11 11:48:34', $secondStep['acted_at']);
        $this->assertSame('2026-08-11T11:48:34+00:00', $approvalAfter['metadata']['acted_at']);
        $this->assertSame((string) $secondStep['id'], $approvalAfter['metadata']['process_step_id']);
    }

    public function test_explicit_attachment_request_workflow_repair_migration_skips_an_allowlisted_request_when_its_shape_changed(): void
    {
        $legacy = $this->reproduceSuppliedLegacyAttachmentRequest('246');
        DB::table('attachment_request_items')
            ->where('id', $legacy['item_id'])
            ->update(['status' => AttachmentRequest::STATUS_PENDING]);

        $before = $this->suppliedLegacyAudit($legacy['request_id']);
        $this->runExplicitAttachmentRequestWorkflowRepairMigration();

        $this->assertSame($before, $this->suppliedLegacyAudit($legacy['request_id']));
    }

    public function test_legacy_attachment_approval_history_migration_reproduces_the_supplied_257_record(): void
    {
        $legacy = $this->reproduceSuppliedLegacyAttachmentRequest('257');
        $before = $this->suppliedLegacyAudit($legacy['request_id']);

        $this->assertSame('.KDC-VD-ABN-DOS-DPR-257-00', $before['request']['serial_number']);
        $this->assertSame('pending', $before['request']['status']);
        $this->assertSame('in_progress', $before['process']['status']);
        $this->assertSame('sequence', $before['process']['execute_type']);
        $this->assertSame([
            ['step_id' => 43, 'template_step_order' => 1, 'status' => 'approved'],
            ['step_id' => 44, 'template_step_order' => 2, 'status' => 'pending'],
        ], $this->sourceStepSummary($before['process_steps']));
        $this->assertSame([
            ['action' => 'request_created', 'template_step_order' => null],
            ['action' => 'workflow_step_approved', 'template_step_order' => 1],
            ['action' => 'workflow_step_pending', 'template_step_order' => 2],
            ['action' => 'workflow_step_pending', 'template_step_order' => 3],
            ['action' => 'attachment_approved', 'template_step_order' => null],
        ], $this->sourceHistorySummary($before['history']));
        $this->assertSame(3, count($before['process']['template_snapshot']));
        $this->assertSame(null, $before['history']['a279a043-86be-4bf8-96f3-7ccfb7a74451']['metadata']['process_step_id']);
        $this->assertSame('dc241aef-d27d-41d7-9723-45309f950471', $before['item']['responded_by_user_id']);

        // This is deliberately the only production-data migration executed here.
        $this->runLegacyAttachmentApprovalHistoryRepairMigration();

        $after = $this->suppliedLegacyAudit($legacy['request_id']);
        $changes = $this->suppliedLegacyChanges($before, $after);
        $this->printSuppliedLegacyReproduction('257 REAL-SHAPE REPRODUCTION', $before, $after, $changes);

        $this->assertSame([
            ['action' => 'request_created', 'template_step_order' => null],
            ['action' => 'workflow_step_approved', 'template_step_order' => 1],
            ['action' => 'attachment_approved', 'template_step_order' => 2],
            ['action' => 'workflow_step_pending', 'template_step_order' => 3],
        ], $this->sourceHistorySummary($after['history']));
        $this->assertSame([
            ['step_id' => 43, 'template_step_order' => 1, 'status' => 'approved'],
            ['step_id' => 44, 'template_step_order' => 2, 'status' => 'approved'],
            ['step_id' => 45, 'template_step_order' => 3, 'status' => 'pending'],
        ], $this->sourceStepSummary($after['process_steps']));
        $this->assertSame('pending', $after['request']['status']);
        $this->assertSame('in_progress', $after['process']['status']);

        $secondStep = $after['process_steps']['a279e89e-c0a3-4654-8bdc-1d3374ce109b'];
        $thirdStep = collect($after['process_steps'])->firstWhere('template_step_order', 3);
        $approval = $after['history']['a279eddd-97d7-4976-8c68-3d67096ee2f8'];
        $thirdPending = $after['history']['a279a043-86be-4bf8-96f3-7ccfb7a74451'];

        $this->assertSame('dc241aef-d27d-41d7-9723-45309f950471', $secondStep['action_by']);
        $this->assertSame('2026-08-11 10:54:45', $secondStep['acted_at']);
        $this->assertSame((string) $secondStep['id'], $approval['metadata']['process_step_id']);
        $this->assertSame(44, $approval['metadata']['step_id']);
        $this->assertSame(2, $approval['metadata']['template_step_order']);
        $this->assertSame((string) $thirdStep['id'], $thirdPending['metadata']['process_step_id']);
        $this->assertSame('b5b3b7a3-615a-409f-b609-fbe02bc2a2a7', $thirdStep['assigned_user_id']);
        $this->assertArrayNotHasKey('a279a043-8532-47c0-bb54-6b284342f27a', $after['history']);
        $this->assertSame([
            ['table' => 'process_steps', 'operation' => 'updated', 'id' => 'a279e89e-c0a3-4654-8bdc-1d3374ce109b'],
            ['table' => 'process_steps', 'operation' => 'inserted', 'id' => $thirdStep['id']],
            ['table' => 'attachment_request_history', 'operation' => 'deleted', 'id' => 'a279a043-8532-47c0-bb54-6b284342f27a'],
            ['table' => 'attachment_request_history', 'operation' => 'updated', 'id' => 'a279a043-86be-4bf8-96f3-7ccfb7a74451'],
            ['table' => 'attachment_request_history', 'operation' => 'updated', 'id' => 'a279eddd-97d7-4976-8c68-3d67096ee2f8'],
        ], $this->sourceChangeSummary($changes));
    }

    public function test_legacy_attachment_approval_history_migration_reconstructs_step_three_for_the_supplied_246_shape_without_history(): void
    {
        $legacy = $this->reproduceSuppliedLegacyAttachmentRequest('246');
        $before = $this->suppliedLegacyAudit($legacy['request_id']);

        $this->assertSame('KDC-VD-ABN-DOS-DPR-246-00', $before['request']['serial_number']);
        $this->assertSame([
            ['step_id' => 43, 'template_step_order' => 1, 'status' => 'approved'],
            ['step_id' => 44, 'template_step_order' => 2, 'status' => 'pending'],
        ], $this->sourceStepSummary($before['process_steps']));
        $this->assertSame(3, count($before['process']['template_snapshot']));
        $this->assertSame([
            ['action' => 'request_created', 'template_step_order' => null],
            ['action' => 'workflow_step_approved', 'template_step_order' => 1],
            ['action' => 'workflow_step_pending', 'template_step_order' => 2],
            ['action' => 'attachment_approved', 'template_step_order' => null],
        ], $this->sourceHistorySummary($before['history']));

        // The source response supplies no Step 3 pending history. The immutable
        // snapshot is now authoritative for creating that missing next lifecycle.
        $this->runLegacyAttachmentApprovalHistoryRepairMigration();

        $after = $this->suppliedLegacyAudit($legacy['request_id']);
        $changes = $this->suppliedLegacyChanges($before, $after);
        $this->printSuppliedLegacyReproduction('246–253 REAL-SHAPE REPRODUCTION', $before, $after, $changes);

        $this->assertSame([
            ['action' => 'request_created', 'template_step_order' => null],
            ['action' => 'workflow_step_approved', 'template_step_order' => 1],
            ['action' => 'attachment_approved', 'template_step_order' => 2],
            ['action' => 'workflow_step_pending', 'template_step_order' => 3],
        ], $this->sourceHistorySummary($after['history']));
        $this->assertSame([
            ['step_id' => 43, 'template_step_order' => 1, 'status' => 'approved'],
            ['step_id' => 44, 'template_step_order' => 2, 'status' => 'approved'],
            ['step_id' => 45, 'template_step_order' => 3, 'status' => 'pending'],
        ], $this->sourceStepSummary($after['process_steps']));
        $this->assertSame('pending', $after['request']['status']);
        $this->assertSame('in_progress', $after['process']['status']);

        $secondStep = $after['process_steps']['a279f212-5a6e-4d47-907d-fd50a2ea4e28'];
        $thirdStep = collect($after['process_steps'])->firstWhere('template_step_order', 3);
        $thirdPending = collect($after['history'])
            ->first(fn (array $history): bool => ($history['metadata']['template_step_order'] ?? null) === 3);
        $approval = $after['history']['a27a0148-67be-4602-a4cb-f52a9fa63513'];

        $this->assertSame('dc241aef-d27d-41d7-9723-45309f950471', $secondStep['action_by']);
        $this->assertSame('2026-08-11 11:48:34', $secondStep['acted_at']);
        $this->assertSame((string) $thirdStep['id'], $thirdPending['metadata']['process_step_id']);
        $this->assertSame('b5b3b7a3-615a-409f-b609-fbe02bc2a2a7', $thirdPending['user_id']);
        $this->assertSame((string) $secondStep['id'], $approval['metadata']['process_step_id']);
        $this->assertArrayNotHasKey('a279f212-a67d-4ce9-bf2d-4d295e3967bb', $after['history']);
        $this->assertSame([
            ['table' => 'process_steps', 'operation' => 'updated', 'id' => 'a279f212-5a6e-4d47-907d-fd50a2ea4e28'],
            ['table' => 'process_steps', 'operation' => 'inserted', 'id' => $thirdStep['id']],
            ['table' => 'attachment_request_history', 'operation' => 'deleted', 'id' => 'a279f212-a67d-4ce9-bf2d-4d295e3967bb'],
            ['table' => 'attachment_request_history', 'operation' => 'updated', 'id' => 'a27a0148-67be-4602-a4cb-f52a9fa63513'],
            ['table' => 'attachment_request_history', 'operation' => 'inserted', 'id' => $thirdPending['id']],
        ], $this->sourceChangeSummary($changes));
    }

    public function test_legacy_attachment_approval_history_migration_completes_a_source_shape_final_step(): void
    {
        $legacy = $this->reproduceSuppliedLegacyAttachmentRequest('246', finalStep: true);
        $before = $this->suppliedLegacyAudit($legacy['request_id']);

        $this->assertSame(2, count($before['process']['template_snapshot']));
        $this->assertSame([
            ['step_id' => 43, 'template_step_order' => 1, 'status' => 'approved'],
            ['step_id' => 44, 'template_step_order' => 2, 'status' => 'pending'],
        ], $this->sourceStepSummary($before['process_steps']));

        $this->runLegacyAttachmentApprovalHistoryRepairMigration();

        $after = $this->suppliedLegacyAudit($legacy['request_id']);
        $changes = $this->suppliedLegacyChanges($before, $after);
        $this->printSuppliedLegacyReproduction('FINAL-STEP REAL-SHAPE REPRODUCTION', $before, $after, $changes);

        $this->assertSame([
            ['action' => 'request_created', 'template_step_order' => null],
            ['action' => 'workflow_step_approved', 'template_step_order' => 1],
            ['action' => 'attachment_approved', 'template_step_order' => 2],
        ], $this->sourceHistorySummary($after['history']));
        $this->assertSame([
            ['step_id' => 43, 'template_step_order' => 1, 'status' => 'approved'],
            ['step_id' => 44, 'template_step_order' => 2, 'status' => 'approved'],
        ], $this->sourceStepSummary($after['process_steps']));
        $this->assertSame('approved', $after['request']['status']);
        $this->assertSame('completed', $after['process']['status']);
        $this->assertArrayNotHasKey('a279f212-a67d-4ce9-bf2d-4d295e3967bb', $after['history']);
        $this->assertSame(2, count($after['process_steps']));
        $this->assertSame([
            ['table' => 'attachment_requests', 'operation' => 'updated', 'id' => '0f714ab2-2f64-4d05-ab81-6a033722466b'],
            ['table' => 'processes', 'operation' => 'updated', 'id' => 'a26d5ee6-6b49-429f-90e4-9f7e4c2c2c7d'],
            ['table' => 'process_steps', 'operation' => 'updated', 'id' => 'a279f212-5a6e-4d47-907d-fd50a2ea4e28'],
            ['table' => 'attachment_request_history', 'operation' => 'deleted', 'id' => 'a279f212-a67d-4ce9-bf2d-4d295e3967bb'],
            ['table' => 'attachment_request_history', 'operation' => 'updated', 'id' => 'a27a0148-67be-4602-a4cb-f52a9fa63513'],
        ], $this->sourceChangeSummary($changes));
    }

    public function test_legacy_attachment_approval_history_migration_leaves_valid_workflows_unchanged(): void
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

        $createResponse = $this->postAttachmentRequest($project, $procedure)->assertOk();
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

        $historyBefore = $this->historySnapshot($requestId);
        $requestBefore = $this->requestSnapshot($requestId);
        $processBefore = $this->processSnapshot($requestId);
        $stepsBefore = $this->processStepsSnapshot($requestId);

        $this->runLegacyAttachmentApprovalHistoryRepairMigration();
        $this->runLegacyAttachmentApprovalHistoryRepairMigration();

        $this->assertSame($historyBefore, $this->historySnapshot($requestId));
        $this->assertSame($requestBefore, $this->requestSnapshot($requestId));
        $this->assertSame($processBefore, $this->processSnapshot($requestId));
        $this->assertSame($stepsBefore, $this->processStepsSnapshot($requestId));
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

    /**
     * Reproduces the persisted legacy state from the supplied production API
     * response using only the values exposed by that response. Supporting local
     * project/procedure records exist only to satisfy local-test foreign keys.
     *
     * The 246 response does not include Step 3 history. Its three-step fixture
     * therefore proves reconstruction directly from template_snapshot. The final
     * variant intentionally truncates that same authoritative local snapshot at
     * Step 2 to verify final-step completion separately.
     *
     * @return array{request_id: string, process_id: string, item_id: string}
     */
    private function reproduceSuppliedLegacyAttachmentRequest(string $record, bool $finalStep = false): array
    {
        $source = $this->suppliedLegacySource($record);
        if ($finalStep) {
            array_pop($source['template_snapshot']);
        }

        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);

        foreach ($source['users'] as $userId => $user) {
            $this->createSuppliedFixtureUser($userId, $user['name'], $user['email']);
        }

        foreach ($source['template_snapshot'] as $snapshot) {
            $this->createSuppliedFixtureTemplateStep(
                (int) $snapshot['step_id'],
                (int) $snapshot['template_step_order'],
                $procedure,
                $snapshot['authorized_user_ids'],
            );
        }

        DB::table('attachment_requests')->insert([
            'id' => $source['request']['id'],
            'serial_number' => $source['request']['serial_number'],
            'name' => $source['request']['serial_number'],
            'date' => $source['request']['date'],
            'project_id' => $project->id,
            'procedure_setting_id' => $procedure->procedure_setting_id,
            'sender_company_id' => $this->company->id,
            'status' => 'pending',
            'created_by_user_id' => self::sourceCreatorId(),
            'responded_by_user_id' => null,
            'responded_at' => null,
            'notes' => null,
            'created_at' => $source['request']['created_at'],
            'updated_at' => $source['request']['created_at'],
        ]);

        DB::table('attachment_request_items')->insert([
            'id' => $source['item']['id'],
            'attachment_request_id' => $source['request']['id'],
            'file_name' => $source['item']['file_name'],
            'file_path' => $source['item']['file_path'],
            'file_type' => 'application/pdf',
            'file_size' => $source['item']['file_size'],
            'status' => 'approved',
            'responded_by_user_id' => self::sourceAttachmentActorId(),
            'responded_at' => $source['item']['responded_at'],
            'response_notes' => null,
            'created_at' => $source['item']['created_at'],
            'updated_at' => $source['item']['responded_at'],
        ]);

        DB::table('processes')->insert([
            'id' => $source['process']['id'],
            'processable_id' => $source['request']['id'],
            'processable_type' => AttachmentRequest::PROCESSABLE_TYPE,
            'user_id' => null,
            'sort_order' => 16,
            'execute_type' => 'sequence',
            'status' => 'in_progress',
            'template_snapshot' => $this->historyJson($source['template_snapshot']),
            'procedure_setting_id' => null,
            'metadata' => null,
            'created_at' => $source['process']['created_at'],
            'updated_at' => $source['process']['updated_at'],
        ]);

        foreach ($source['process_steps'] as $step) {
            DB::table('process_steps')->insert([
                'id' => $step['id'],
                'process_id' => $source['process']['id'],
                'step_id' => $step['step_id'],
                'template_step_order' => $step['template_step_order'],
                'assigned_user_id' => $step['assigned_user_id'],
                'authorized_user_ids' => $this->historyJson($step['authorized_user_ids']),
                'escalation_management_hierarchy_id' => null,
                'status' => $step['status'],
                'action_by' => $step['action_by'],
                'acted_at' => $step['acted_at'],
                'created_at' => $step['created_at'],
                'updated_at' => $step['updated_at'],
            ]);
        }

        foreach ($source['history'] as $history) {
            DB::table('attachment_request_history')->insert([
                'id' => $history['id'],
                'attachment_request_id' => $source['request']['id'],
                // The API exposes this same source UUID in metadata. Keeping the
                // relational link local makes the item/actor proof exact too.
                'attachment_request_item_id' => $history['attachment_request_item_id'],
                'action' => $history['action'],
                'description' => $history['description'],
                'user_id' => $history['user_id'],
                'metadata' => $this->historyJson($history['metadata']),
                'dedupe_key' => null,
                'sort_order' => $history['sort_order'],
                'created_at' => $history['created_at'],
            ]);
        }

        return [
            'request_id' => $source['request']['id'],
            'process_id' => $source['process']['id'],
            'item_id' => $source['item']['id'],
        ];
    }

    private function createSuppliedFixtureUser(string $id, string $name, string $email): void
    {
        if (DB::table('users')->where('id', $id)->exists()) {
            return;
        }

        // UuidTrait intentionally replaces every model-created ID. Insert the
        // factory's valid local attributes directly so the source UUID remains
        // the real FK identity used by the fixture.
        $attributes = User::factory()->raw([
            'company_id' => $this->company->id,
            'name' => $name,
            'email' => $email,
        ]);
        $attributes['id'] = $id;
        $attributes['created_at'] = '2026-08-01 00:00:00';
        $attributes['updated_at'] = '2026-08-01 00:00:00';

        DB::table('users')->insert($attributes);
    }

    /**
     * @param  list<string>  $authorizedUserIds
     */
    private function createSuppliedFixtureTemplateStep(
        int $id,
        int $order,
        ProjectProcedureSetting $procedure,
        array $authorizedUserIds,
    ): void {
        if (! DB::table('procedure_setting_steps')->where('id', $id)->exists()) {
            DB::table('procedure_setting_steps')->insert([
                'id' => $id,
                'name' => "Source API workflow step {$id}",
                'is_accept' => false,
                'is_approve' => true,
                'forms' => 'approve',
                'is_view_only' => false,
                'is_return_with_notes' => false,
                'requires_approval_within_period' => false,
                'procedure_setting_id' => $procedure->procedure_setting_id,
                'company_id' => $this->company->id,
                'project_id' => $procedure->project_id,
                'step_order' => $order,
                'action_taker_type' => 'specific_user',
                'created_at' => '2026-08-01 00:00:00',
                'updated_at' => '2026-08-01 00:00:00',
            ]);
        }

        foreach ($authorizedUserIds as $userId) {
            DB::table('procedure_setting_step_action_takers')->insertOrIgnore([
                'procedure_setting_step_id' => $id,
                'user_id' => $userId,
                'company_id' => $this->company->id,
                'created_at' => '2026-08-01 00:00:00',
                'updated_at' => '2026-08-01 00:00:00',
            ]);
        }
    }

    /**
     * @return array{
     *     request: array<string, mixed>,
     *     process: array<string, mixed>,
     *     item: array<string, mixed>,
     *     process_steps: list<array<string, mixed>>,
     *     template_snapshot: list<array<string, mixed>>,
     *     history: list<array<string, mixed>>,
     *     users: array<string, array{name: string, email: string}>
     * }
     */
    private function suppliedLegacySource(string $record): array
    {
        $firstStepAuthorizedUsers257 = [
            'a353c1bc-a6fa-4283-a8a9-904f2063fd25',
            '89ab7f5a-3570-4c92-af1d-020b1d62c9da',
            '6e79606a-b634-4e15-81ff-ad77ff963285',
            '48a578e4-ad4d-4db6-91b6-995c5fa7f197',
            '4bfca16c-28e4-494b-a30b-2de23f6a1f01',
            '055f54cc-f759-4686-b48c-9c32af8c28d7',
            'b11b79e8-d146-4a9e-851d-86c34e95a1f5',
            'c520183b-298f-46a6-abdc-4fc602cd07e3',
            'b8910f08-e40a-4560-b67e-dc90cf40a37e',
            'a7abf5ec-6121-4a72-a97c-47fea0b07ece',
            '46e81fae-0bca-4214-bea3-75b4261204db',
        ];
        $attachmentActor = self::sourceAttachmentActorId();
        $thirdStepActor = self::sourceThirdStepActorId();

        $source = match ($record) {
            '257' => [
                'request' => [
                    'id' => '529fdf4d-7fee-4105-b079-29bc0e8a063a',
                    'serial_number' => '.KDC-VD-ABN-DOS-DPR-257-00',
                    'date' => '2026-08-11',
                    'created_at' => '2026-08-11 07:17:13',
                    'history_created_at' => '2026-08-11 07:17:15',
                ],
                'process' => [
                    'id' => 'a279a043-81a1-4317-85e3-aebbe806a7c9',
                    'created_at' => '2026-08-11 07:17:15',
                    'updated_at' => '2026-08-11 07:17:15',
                ],
                'item' => [
                    'id' => '9c5dee52-2475-4378-a14a-480d84627c47',
                    'file_name' => 'KDC-VD-ABN-DOS-DPR-257-00.pdf',
                    'file_path' => 'attachment-requests/KDC-VD-ABN-DOS-DPR-257-00_6a7acc79d0b92.pdf',
                    'file_size' => 57858832,
                    'created_at' => '2026-08-11 07:17:13',
                    'responded_at' => '2026-08-11 10:54:45',
                ],
                'first_step' => [
                    'id' => 'a279a043-887f-411b-b537-efd00ad4a682',
                    'assigned_user_id' => 'a353c1bc-a6fa-4283-a8a9-904f2063fd25',
                    'authorized_user_ids' => $firstStepAuthorizedUsers257,
                    'action_by' => '48a578e4-ad4d-4db6-91b6-995c5fa7f197',
                    'acted_at' => '2026-08-11 10:39:34',
                    'created_at' => '2026-08-11 07:17:15',
                    'updated_at' => '2026-08-11 10:39:34',
                    'history_id' => 'a279a043-8387-42e7-9312-ac7688830c7a',
                    'history_created_at' => '2026-08-11 07:17:15',
                ],
                'second_step' => [
                    'id' => 'a279e89e-c0a3-4654-8bdc-1d3374ce109b',
                    'history_id' => 'a279a043-8532-47c0-bb54-6b284342f27a',
                    'history_created_at' => '2026-08-11 07:17:15',
                ],
                'third_history' => [
                    'id' => 'a279a043-86be-4bf8-96f3-7ccfb7a74451',
                    'created_at' => '2026-08-11 07:17:15',
                ],
                'approval_history' => [
                    'id' => 'a279eddd-97d7-4976-8c68-3d67096ee2f8',
                    'created_at' => '2026-08-11 10:54:14',
                ],
            ],
            '246' => [
                'request' => [
                    'id' => '0f714ab2-2f64-4d05-ab81-6a033722466b',
                    'serial_number' => 'KDC-VD-ABN-DOS-DPR-246-00',
                    'date' => '2026-08-05',
                    'created_at' => '2026-08-05 05:04:32',
                    'history_created_at' => '2026-08-05 05:04:32',
                ],
                'process' => [
                    'id' => 'a26d5ee6-6b49-429f-90e4-9f7e4c2c2c7d',
                    'created_at' => '2026-08-05 05:04:32',
                    'updated_at' => '2026-08-05 05:04:32',
                ],
                'item' => [
                    'id' => 'f1dfd122-8bfa-49b4-8710-36f93592f968',
                    'file_name' => 'KDC-VD-ABN-DOS-DPR-246-00.pdf',
                    'file_path' => 'attachment-requests/KDC-VD-ABN-DOS-DPR-246-00_6a72c46002cec.pdf',
                    'file_size' => 39300344,
                    'created_at' => '2026-08-05 05:04:32',
                    'responded_at' => '2026-08-11 11:48:34',
                ],
                'first_step' => [
                    'id' => 'a26d5ee6-6cd2-4807-b290-31e998696d67',
                    'assigned_user_id' => '46e81fae-0bca-4214-bea3-75b4261204db',
                    'authorized_user_ids' => ['46e81fae-0bca-4214-bea3-75b4261204db'],
                    'action_by' => '46e81fae-0bca-4214-bea3-75b4261204db',
                    'acted_at' => '2026-08-11 11:06:00',
                    'created_at' => '2026-08-05 05:04:32',
                    'updated_at' => '2026-08-11 11:06:00',
                    'history_id' => 'a279f212-584e-43f5-860e-57791c20df52',
                    'history_created_at' => '2026-08-05 05:04:32',
                ],
                'second_step' => [
                    'id' => 'a279f212-5a6e-4d47-907d-fd50a2ea4e28',
                    'history_id' => 'a279f212-a67d-4ce9-bf2d-4d295e3967bb',
                    'history_created_at' => '2026-08-11 11:06:00',
                ],
                'third_history' => null,
                'approval_history' => [
                    'id' => 'a27a0148-67be-4602-a4cb-f52a9fa63513',
                    'created_at' => '2026-08-11 11:48:32',
                ],
            ],
            default => throw new \InvalidArgumentException("Unsupported supplied legacy source: {$record}"),
        };

        $snapshot = [
            $this->suppliedSnapshotStep(43, 1, $source['first_step']['assigned_user_id'], $source['first_step']['authorized_user_ids']),
            $this->suppliedSnapshotStep(44, 2, $attachmentActor, [$attachmentActor]),
            // The 257 record supplies the Step 3 identity. The requested 246
            // scenario uses this three-step snapshot but deliberately omits the
            // historical Step 3 activation that 246's API response does not have.
            $this->suppliedSnapshotStep(45, 3, $thirdStepActor, [$thirdStepActor]),
        ];

        $firstStepMetadata = $this->suppliedWorkflowMetadata(
            $source['process']['id'],
            $source['first_step']['id'],
            43,
            1,
            $source['first_step']['assigned_user_id'],
            $source['first_step']['authorized_user_ids'],
            'approved',
            $this->isoUtc($source['first_step']['acted_at']),
        );
        $secondStepMetadata = $this->suppliedWorkflowMetadata(
            $source['process']['id'],
            $source['second_step']['id'],
            44,
            2,
            $attachmentActor,
            [$attachmentActor],
            'pending',
            null,
        );

        $history = [
            [
                'id' => $this->requestCreatedHistoryId($record),
                'attachment_request_item_id' => null,
                'action' => 'request_created',
                'description' => 'Attachment request created',
                'user_id' => self::sourceCreatorId(),
                'metadata' => [
                    'request_name' => $source['request']['serial_number'],
                    'total_attachments' => 1,
                    'procedure_setting_id' => '123cb1bb-d8bf-45f0-9215-548983bba21e',
                ],
                'sort_order' => 0,
                'created_at' => $source['request']['history_created_at'],
            ],
            [
                'id' => $source['first_step']['history_id'],
                'attachment_request_item_id' => null,
                'action' => 'workflow_step_approved',
                'description' => 'Workflow step approved',
                'user_id' => $source['first_step']['action_by'],
                'metadata' => $firstStepMetadata,
                'sort_order' => 116001,
                'created_at' => $source['first_step']['history_created_at'],
            ],
            [
                'id' => $source['second_step']['history_id'],
                'attachment_request_item_id' => null,
                'action' => 'workflow_step_pending',
                'description' => 'Workflow step pending',
                'user_id' => $attachmentActor,
                'metadata' => $secondStepMetadata,
                'sort_order' => 116002,
                'created_at' => $source['second_step']['history_created_at'],
            ],
        ];

        if ($source['third_history'] !== null) {
            $history[] = [
                'id' => $source['third_history']['id'],
                'attachment_request_item_id' => null,
                'action' => 'workflow_step_pending',
                'description' => 'Workflow step pending',
                'user_id' => $thirdStepActor,
                'metadata' => $this->suppliedWorkflowMetadata(
                    $source['process']['id'],
                    null, 45, 3, $thirdStepActor, [$thirdStepActor], 'pending', null,
                ),
                'sort_order' => 116003,
                'created_at' => $source['third_history']['created_at'],
            ];
        }

        $history[] = [
            'id' => $source['approval_history']['id'],
            'attachment_request_item_id' => $source['item']['id'],
            'action' => 'attachment_approved',
            'description' => 'Attachment approved',
            'user_id' => $attachmentActor,
            'metadata' => [
                'status' => 'approved',
                'item_id' => $source['item']['id'],
                'file_url' => 'http://core-be-production.constrix-nv.com/storage/'.$source['item']['file_path'],
                'file_name' => $source['item']['file_name'],
                'file_path' => $source['item']['file_path'],
                'file_size' => $source['item']['file_size'],
                'file_type' => 'application/pdf',
                'process_id' => $source['process']['id'],
                'response_notes' => null,
                'previous_status' => 'pending',
                'process_sort_order' => 16,
                'file_size_formatted' => $record === '257' ? '55.18 MB' : '37.48 MB',
            ],
            'sort_order' => $source['third_history'] === null ? 116003 : 116004,
            'created_at' => $source['approval_history']['created_at'],
        ];

        return [
            'request' => $source['request'],
            'process' => $source['process'],
            'item' => $source['item'],
            'process_steps' => [
                [
                    'id' => $source['first_step']['id'],
                    'step_id' => 43,
                    'template_step_order' => 1,
                    'assigned_user_id' => $source['first_step']['assigned_user_id'],
                    'authorized_user_ids' => $source['first_step']['authorized_user_ids'],
                    'status' => 'approved',
                    'action_by' => $source['first_step']['action_by'],
                    'acted_at' => $source['first_step']['acted_at'],
                    'created_at' => $source['first_step']['created_at'],
                    'updated_at' => $source['first_step']['updated_at'],
                ],
                [
                    'id' => $source['second_step']['id'],
                    'step_id' => 44,
                    'template_step_order' => 2,
                    'assigned_user_id' => $attachmentActor,
                    'authorized_user_ids' => [$attachmentActor],
                    'status' => 'pending',
                    'action_by' => null,
                    'acted_at' => null,
                    'created_at' => $source['first_step']['updated_at'],
                    'updated_at' => $source['first_step']['updated_at'],
                ],
            ],
            'template_snapshot' => $snapshot,
            'history' => $history,
            'users' => $this->suppliedLegacyUsers(array_merge(
                $source['first_step']['authorized_user_ids'],
                [$attachmentActor, $thirdStepActor, self::sourceCreatorId()],
            )),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function suppliedSnapshotStep(
        int $stepId,
        int $order,
        string $assignedUserId,
        array $authorizedUserIds,
    ): array {
        return [
            'step_id' => $stepId,
            'template_step_order' => $order,
            'assigned_user_id' => $assignedUserId,
            'authorized_user_ids' => $authorizedUserIds,
            'specific_procedure_types' => [],
            'action_taker_type' => 'specific_user',
            'escalation_management_hierarchy_id' => null,
        ];
    }

    /**
     * @param  list<string>  $authorizedUserIds
     * @return array<string, mixed>
     */
    private function suppliedWorkflowMetadata(
        string $processId,
        ?string $processStepId,
        int $stepId,
        int $templateStepOrder,
        string $assignedUserId,
        array $authorizedUserIds,
        string $status,
        ?string $actedAt,
    ): array {
        return [
            'status' => $status,
            'step_id' => $stepId,
            'acted_at' => $actedAt,
            'process_id' => $processId,
            'process_step_id' => $processStepId,
            'assigned_user_id' => $assignedUserId,
            'is_auto_approved' => false,
            'process_sort_order' => 16,
            'authorized_user_ids' => $authorizedUserIds,
            'template_step_order' => $templateStepOrder,
        ];
    }

    /**
     * @param  list<string>  $ids
     * @return array<string, array{name: string, email: string}>
     */
    private function suppliedLegacyUsers(array $ids): array
    {
        $knownUsers = [
            self::sourceCreatorId() => ['name' => 'محمد عشماوي', 'email' => 'm.ashmawy@abn.sa.com'],
            '48a578e4-ad4d-4db6-91b6-995c5fa7f197' => ['name' => 'محمد صلاح حسين', 'email' => 'muhammadsalahal-din-civ-jed@vd-2030.com'],
            self::sourceAttachmentActorId() => ['name' => 'جمال السيد محمد', 'email' => 'eng.gamal-jed@vd-2030.com'],
            self::sourceThirdStepActorId() => ['name' => 'بشير نبوي ضيف', 'email' => 'bndeif@kidana.com.sa'],
            '46e81fae-0bca-4214-bea3-75b4261204db' => ['name' => 'محمد السر علي الحسن', 'email' => 'm.ali-mk@vd-2030.com'],
        ];

        $users = [];
        foreach (array_unique($ids) as $id) {
            $users[$id] = $knownUsers[$id] ?? [
                'name' => "Source fixture user {$id}",
                'email' => 'source-'.str_replace('-', '', $id).'@example.test',
            ];
        }

        return $users;
    }

    private static function sourceCreatorId(): string
    {
        return 'df9c6212-d355-4475-b66e-2679bbdeaa00';
    }

    private static function sourceAttachmentActorId(): string
    {
        return 'dc241aef-d27d-41d7-9723-45309f950471';
    }

    private static function sourceThirdStepActorId(): string
    {
        return 'b5b3b7a3-615a-409f-b609-fbe02bc2a2a7';
    }

    private function requestCreatedHistoryId(string $record): string
    {
        return $record === '257'
            ? 'a279a043-7b32-4e34-8e9b-65edbfd4dde2'
            : 'a26d5ee6-66ee-491d-ad56-600df37d80d3';
    }

    private function isoUtc(string $dateTime): string
    {
        return str_replace(' ', 'T', $dateTime).'+00:00';
    }

    /**
     * @return array{
     *     request: array<string, mixed>,
     *     item: array<string, mixed>,
     *     process: array<string, mixed>,
     *     process_steps: array<string, array<string, mixed>>,
     *     history: array<string, array<string, mixed>>
     * }
     */
    private function suppliedLegacyAudit(string $requestId): array
    {
        $request = (array) DB::table('attachment_requests')->where('id', $requestId)->firstOrFail();
        $process = (array) DB::table('processes')
            ->where('processable_id', $requestId)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->firstOrFail();
        $item = (array) DB::table('attachment_request_items')
            ->where('attachment_request_id', $requestId)
            ->firstOrFail();

        $request = array_intersect_key($request, array_flip([
            'id', 'serial_number', 'status', 'created_by_user_id', 'responded_by_user_id', 'responded_at', 'created_at',
        ]));
        $item = array_intersect_key($item, array_flip([
            'id', 'attachment_request_id', 'file_name', 'file_path', 'file_type', 'file_size', 'status',
            'responded_by_user_id', 'responded_at', 'response_notes', 'created_at', 'updated_at',
        ]));
        $process = array_intersect_key($process, array_flip([
            'id', 'processable_id', 'processable_type', 'sort_order', 'execute_type', 'status', 'template_snapshot',
            'created_at', 'updated_at',
        ]));
        $process['template_snapshot'] = $this->historyMetadata(
            (object) ['metadata' => $process['template_snapshot']]
        );

        $steps = DB::table('process_steps')
            ->where('process_id', $process['id'])
            ->orderBy('template_step_order')
            ->orderBy('id')
            ->get()
            ->map(function (object $step): array {
                $row = (array) $step;
                $row['authorized_user_ids'] = $this->historyMetadata((object) ['metadata' => $row['authorized_user_ids']]);

                return $row;
            })
            ->keyBy('id')
            ->all();

        $history = DB::table('attachment_request_history')
            ->where('attachment_request_id', $requestId)
            ->orderByRaw('sort_order is null')
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(function (object $entry): array {
                $row = (array) $entry;
                $row['metadata'] = $this->historyMetadata((object) ['metadata' => $row['metadata']]);

                return $row;
            })
            ->keyBy('id')
            ->all();

        return compact('request', 'item', 'process', 'steps', 'history') + ['process_steps' => $steps];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return list<array{table: string, operation: string, id: string, before?: array<string, mixed>, after?: array<string, mixed>}>
     */
    private function suppliedLegacyChanges(array $before, array $after): array
    {
        $changes = [];
        foreach ([
            'request' => 'attachment_requests',
            'process' => 'processes',
        ] as $key => $table) {
            if (json_encode($before[$key], JSON_THROW_ON_ERROR) !== json_encode($after[$key], JSON_THROW_ON_ERROR)) {
                $changes[] = [
                    'table' => $table,
                    'operation' => 'updated',
                    'id' => (string) $before[$key]['id'],
                    'before' => $before[$key],
                    'after' => $after[$key],
                ];
            }
        }

        foreach ([
            'process_steps' => 'process_steps',
            'history' => 'attachment_request_history',
        ] as $key => $table) {
            $beforeRows = $before[$key];
            $afterRows = $after[$key];
            foreach ($beforeRows as $id => $beforeRow) {
                if (! array_key_exists($id, $afterRows)) {
                    $changes[] = ['table' => $table, 'operation' => 'deleted', 'id' => (string) $id, 'before' => $beforeRow];

                    continue;
                }

                if (json_encode($beforeRow, JSON_THROW_ON_ERROR) !== json_encode($afterRows[$id], JSON_THROW_ON_ERROR)) {
                    $changes[] = [
                        'table' => $table,
                        'operation' => 'updated',
                        'id' => (string) $id,
                        'before' => $beforeRow,
                        'after' => $afterRows[$id],
                    ];
                }
            }
            foreach ($afterRows as $id => $afterRow) {
                if (! array_key_exists($id, $beforeRows)) {
                    $changes[] = ['table' => $table, 'operation' => 'inserted', 'id' => (string) $id, 'after' => $afterRow];
                }
            }
        }

        return $changes;
    }

    /**
     * @param  array<string, array<string, mixed>>  $steps
     * @return list<array{step_id: int, template_step_order: int, status: string}>
     */
    private function sourceStepSummary(array $steps): array
    {
        return collect($steps)
            ->sortBy('template_step_order')
            ->map(static fn (array $step): array => [
                'step_id' => (int) $step['step_id'],
                'template_step_order' => (int) $step['template_step_order'],
                'status' => (string) $step['status'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, array<string, mixed>>  $history
     * @return list<array{action: string, template_step_order: int|null}>
     */
    private function sourceHistorySummary(array $history): array
    {
        return collect($history)
            ->map(static fn (array $entry): array => [
                'action' => (string) $entry['action'],
                'template_step_order' => isset($entry['metadata']['template_step_order'])
                    ? (int) $entry['metadata']['template_step_order']
                    : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $changes
     * @return list<array{table: string, operation: string, id: string}>
     */
    private function sourceChangeSummary(array $changes): array
    {
        return array_map(static fn (array $change): array => [
            'table' => $change['table'],
            'operation' => $change['operation'],
            'id' => $change['id'],
        ], $changes);
    }

    /**
     * The explicit flag keeps ordinary PHPUnit output concise. The local
     * verification command enables it and prints every persisted row before,
     * after, and changed by this one migration.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<array<string, mixed>>  $changes
     */
    private function printSuppliedLegacyReproduction(string $label, array $before, array $after, array $changes): void
    {
        if (getenv('LEGACY_MIGRATION_REPRO_REPORT') !== '1') {
            return;
        }

        $output = [
            "\n===== {$label} =====",
            'TEMPLATE SNAPSHOT',
            json_encode($before['process']['template_snapshot'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'BEFORE REQUEST / PROCESS',
            json_encode(['request' => $before['request'], 'process' => $before['process']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'BEFORE HISTORY',
            json_encode(array_values($before['history']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'BEFORE PROCESS_STEPS',
            json_encode(array_values($before['process_steps']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'AFTER REQUEST / PROCESS',
            json_encode(['request' => $after['request'], 'process' => $after['process']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'AFTER HISTORY',
            json_encode(array_values($after['history']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'AFTER PROCESS_STEPS',
            json_encode(array_values($after['process_steps']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'ROWS CHANGED BY MIGRATION',
            json_encode($changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            '===== END =====',
        ];

        fwrite(STDOUT, implode(PHP_EOL, $output).PHP_EOL);
    }

    /**
     * Recreate the production legacy gap from a clean request. The initial
     * workflow is allowed to generate its authentic snapshot/history, then its
     * Step 2 completion and (for Case A) Step 3 persistence are rolled back to
     * the exact corrupted state observed in legacy data.
     *
     * @return array<string, mixed>
     */
    private function recreateMissingSequentialAdvanceLegacyCase(int $stepCount): array
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $firstReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $thirdReceiverUser = $stepCount === 3
            ? User::factory()->create(['company_id' => $receiverCompany->id])
            : null;

        $this->createAcceptedShare($project, $receiverCompany);
        $this->createProcedureStep($procedure, $firstReceiverUser, 1);
        $this->createProcedureStep($procedure, $secondReceiverUser, 2);
        if ($thirdReceiverUser !== null) {
            $this->createProcedureStep($procedure, $thirdReceiverUser, 3);
        }

        $createResponse = $this->postAttachmentRequest($project, $procedure)->assertOk();
        $requestId = $createResponse->json('payload.id');
        $itemId = $createResponse->json('payload.items.0.id');

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post("/api/v1/projects/attachment-requests/{$requestId}/approve", [], ['Accept' => 'application/json'])
            ->assertOk();

        $this->respondToAttachmentItem($secondReceiverUser, $receiverCompany, $itemId, 'approve');

        $process = Process::query()
            ->where('processable_id', $requestId)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->firstOrFail()
            ->load('steps');
        $steps = $process->steps->sortBy('template_step_order')->values();
        $secondStep = $steps[1];
        $thirdStep = $stepCount === 3 ? $steps[2] : null;
        $processSortOrder = (int) ($process->sort_order ?? 0);
        $approval = AttachmentRequestHistory::query()
            ->where('attachment_request_id', $requestId)
            ->where('action', 'attachment_approved')
            ->firstOrFail();
        $item = AttachmentRequestItem::query()->findOrFail($itemId);

        $thirdPendingHistory = $thirdStep === null
            ? null
            : AttachmentRequestHistory::query()
                ->where('attachment_request_id', $requestId)
                ->where('action', 'workflow_step_pending')
                ->get()
                ->first(fn (AttachmentRequestHistory $history): bool => (int) ($history->metadata['template_step_order'] ?? 0)
                    === (int) $thirdStep->template_step_order
                );

        if ($thirdPendingHistory instanceof AttachmentRequestHistory) {
            $thirdMetadata = $thirdPendingHistory->metadata;
            $thirdMetadata['process_step_id'] = null;
            $thirdMetadata['process_id'] = (string) $process->id;
            $thirdMetadata['process_sort_order'] = $processSortOrder;
            $thirdMetadata['step_id'] = (int) $thirdStep->step_id;
            $thirdMetadata['template_step_order'] = (int) $thirdStep->template_step_order;
            $thirdMetadata['assigned_user_id'] = (string) $thirdStep->assigned_user_id;
            $thirdMetadata['authorized_user_ids'] = $thirdStep->authorized_user_ids;
            $thirdMetadata['status'] = ProcessStepStatus::Pending->value;

            $thirdPendingHistory->forceFill([
                'metadata' => $thirdMetadata,
                'sort_order' => 100000 + ($processSortOrder * 1000) + 3,
            ])->save();
        }

        $approvalMetadata = $approval->metadata;
        unset(
            $approvalMetadata['process_id'],
            $approvalMetadata['process_sort_order'],
            $approvalMetadata['process_step_id'],
            $approvalMetadata['step_id'],
            $approvalMetadata['template_step_order'],
            $approvalMetadata['assigned_user_id'],
            $approvalMetadata['authorized_user_ids'],
            $approvalMetadata['acted_at'],
            $approvalMetadata['is_auto_approved'],
        );
        $approval->forceFill([
            'metadata' => $approvalMetadata,
            'sort_order' => 100000 + ($processSortOrder * 1000) + $stepCount + 1,
        ])->save();

        DB::table('process_steps')
            ->where('id', $secondStep->id)
            ->update([
                'status' => ProcessStepStatus::Pending->value,
                'action_by' => null,
                'acted_at' => null,
            ]);

        if ($thirdStep !== null) {
            DB::table('process_steps')->where('id', $thirdStep->id)->delete();
        }

        $stalePendingHistoryId = $this->insertHistoricalHistory(
            requestId: $requestId,
            action: 'workflow_step_pending',
            description: 'Legacy stale pending step two',
            userId: null,
            itemId: null,
            metadata: [
                'process_id' => (string) $process->id,
                'process_sort_order' => $processSortOrder,
                'process_step_id' => (string) $secondStep->id,
                'step_id' => (int) $secondStep->step_id,
                'template_step_order' => (int) $secondStep->template_step_order,
                'assigned_user_id' => (string) $secondStep->assigned_user_id,
                'authorized_user_ids' => $secondStep->authorized_user_ids,
                'status' => ProcessStepStatus::Pending->value,
            ],
            createdAt: $approval->created_at->copy()->subSecond(),
            sortOrder: 100000 + ($processSortOrder * 1000) + 2,
        );

        // Case B's genuine execution would have finalized the request. The legacy
        // state is the point immediately before that final persisted transition.
        AttachmentRequestHistory::query()
            ->where('attachment_request_id', $requestId)
            ->where('action', 'request_approved')
            ->delete();

        DB::table('processes')
            ->where('id', $process->id)
            ->update(['status' => ProcessStatus::InProgress->value]);
        DB::table('attachment_requests')
            ->where('id', $requestId)
            ->update([
                'status' => $stepCount === 3
                    ? AttachmentRequest::STATUS_APPROVED
                    : AttachmentRequest::STATUS_PENDING,
            ]);

        return [
            'request_id' => $requestId,
            'item_id' => $itemId,
            'process_id' => (string) $process->id,
            'process_sort_order' => $processSortOrder,
            'second_step_id' => (string) $secondStep->id,
            'second_template_step_id' => (int) $secondStep->step_id,
            'second_user_id' => (string) $secondReceiverUser->id,
            'approval_history_id' => (string) $approval->id,
            'stale_pending_history_id' => $stalePendingHistoryId,
            'third_pending_history_id' => $thirdPendingHistory?->id,
            'item_responded_at' => (string) $item->responded_at,
        ];
    }

    /**
     * @return array{
     *     history: list<array{action: string, template_step_order: int|null}>,
     *     process_steps: list<array{template_step_order: int, status: string}>,
     *     request_status: string,
     *     process_status: string
     * }
     */
    private function legacyWorkflowAuditSnapshot(string $requestId): array
    {
        $process = DB::table('processes')
            ->where('processable_id', $requestId)
            ->where('processable_type', AttachmentRequest::PROCESSABLE_TYPE)
            ->firstOrFail();

        return [
            'history' => DB::table('attachment_request_history')
                ->where('attachment_request_id', $requestId)
                ->orderByRaw('sort_order is null')
                ->orderBy('sort_order')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
                ->map(fn ($history): array => [
                    'action' => (string) $history->action,
                    'template_step_order' => $this->historyMetadata($history)['template_step_order'] ?? null,
                ])
                ->all(),
            'process_steps' => DB::table('process_steps')
                ->where('process_id', $process->id)
                ->orderBy('template_step_order')
                ->get()
                ->map(static fn ($step): array => [
                    'template_step_order' => (int) $step->template_step_order,
                    'status' => (string) $step->status,
                ])
                ->all(),
            'request_status' => (string) DB::table('attachment_requests')
                ->where('id', $requestId)
                ->value('status'),
            'process_status' => (string) $process->status,
        ];
    }

    private function historyMetadata(object $history): array
    {
        if (is_array($history->metadata)) {
            return $history->metadata;
        }

        return json_decode((string) $history->metadata, true, flags: JSON_THROW_ON_ERROR);
    }

    private function runLegacyAttachmentApprovalHistoryRepairMigration(): void
    {
        $migration = require database_path(
            'migrations/2026_08_20_000000_repair_legacy_attachment_approval_workflow_history.php'
        );

        $migration->up();
    }

    private function runExplicitAttachmentRequestWorkflowRepairMigration(): void
    {
        $migration = require database_path(
            'migrations/2026_08_20_000002_repair_explicit_attachment_request_workflow_history.php'
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
    ): ProjectProcedureSetting {
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

    private function createProjectArchiveRootFolder(ProjectManagement $project): Folder
    {
        return Folder::withoutEvents(fn (): Folder => Folder::query()
            ->withoutGlobalScopes()
            ->forceCreate([
                'id' => $project->id,
                'name' => 'Archive Root',
                'parent_id' => null,
                'project_id' => $project->id,
                'company_id' => $this->company->id,
                'access_type' => 'private',
                'status' => 1,
            ]));
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
