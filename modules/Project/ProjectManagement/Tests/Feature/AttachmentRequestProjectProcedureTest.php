<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\Process\Models\Process;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\ProcedureSetting\Models\ProcedureSettingStepActionTaker;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\Project\ProjectManagement\Models\AttachmentRequest;
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

    public function test_workflow_step_actions_are_logged_from_first_step(): void
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
            ->assertJsonPath('payload.history.1.action', 'workflow_step_approved')
            ->assertJsonPath('payload.history.1.metadata.template_step_order', 1);

        $this->assertDatabaseHas('attachment_request_history', [
            'attachment_request_id' => $requestId,
            'action' => 'workflow_step_approved',
            'user_id' => $firstReceiverUser->id,
        ]);

        $this->actingAs($secondReceiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post("/api/v1/projects/attachment-requests/{$requestId}/approve", [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.status', AttachmentRequest::STATUS_APPROVED)
            ->assertJsonPath('payload.history.2.action', 'workflow_step_approved')
            ->assertJsonPath('payload.history.2.metadata.template_step_order', 2)
            ->assertJsonPath('payload.history.3.action', 'request_approved');
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

        $this->actingAs($receiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post("/api/v1/projects/attachment-requests/{$requestId}/decline", [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.status', AttachmentRequest::STATUS_DECLINED);

        $this->assertDatabaseHas('processes', [
            'processable_id' => $requestId,
            'processable_type' => AttachmentRequest::PROCESSABLE_TYPE,
            'status' => ProcessStatus::Failed->value,
        ]);
        $this->assertDatabaseHas('attachment_request_items', [
            'attachment_request_id' => $requestId,
            'status' => AttachmentRequest::STATUS_DECLINED,
        ]);
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

    private function schemaReady(): bool
    {
        return Schema::hasTable('attachment_requests')
            && Schema::hasColumn('attachment_requests', 'procedure_setting_id')
            && ! Schema::hasColumn('attachment_requests', 'receiver_company_id')
            && ! Schema::hasColumn('attachment_requests', 'attachment_type_id')
            && ! Schema::hasColumn('attachment_requests', 'attachment_sub_type_id')
            && ! Schema::hasColumn('attachment_requests', 'attachment_sub_sub_type_id')
            && Schema::hasTable('attachment_request_items')
            && Schema::hasTable('project_procedure_settings')
            && Schema::hasTable('folders')
            && Schema::hasTable('procedure_settings')
            && Schema::hasTable('procedure_setting_steps')
            && Schema::hasTable('procedure_setting_step_action_takers')
            && Schema::hasTable('processes')
            && Schema::hasTable('process_steps')
            && Schema::hasTable('resource_shares')
            && Schema::hasTable('work_flows')
            && Schema::hasTable('media');
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

    private function createProjectProcedure(ProjectManagement $project): ProjectProcedureSetting
    {
        $workFlow = WorkFlow::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'name' => 'project_'.$project->id,
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
        ]);

        $parent = ProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'name' => 'Project Procedures',
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
            'execute_type' => 'sequence',
            'is_active' => true,
            'work_flow_id' => $workFlow->id,
            'parent_id' => null,
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

        return ProjectProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'procedure_setting_id' => $procedureSetting->id,
            'attachment_type_id' => $attachmentType->id,
            'attachment_sub_type_id' => $attachmentSubType->id,
            'attachment_sub_sub_type_id' => $attachmentSubSubType->id,
            'used_in_document_cycle' => true,
        ]);
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
        ProjectProcedureSetting $procedure
    ) {
        return $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests', [
                'name' => 'Workflow Attachment Files',
                'date' => '2026-07-21',
                'project_id' => $project->id,
                'procedure_setting_id' => $procedure->procedure_setting_id,
                'attachments' => [
                    UploadedFile::fake()->create('workflow-file.pdf', 12, 'application/pdf'),
                ],
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
