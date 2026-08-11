<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\Process\Models\Process;
use Modules\Process\Models\ProcessStep;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementEvaluationStatus;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementRepetition;
use Modules\Project\ProjectManagement\Models\AttachmentRequest;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectProcedureSetting;
use Modules\Project\ProjectManagement\Models\ProjectRequirement;
use Modules\Project\ProjectManagement\Models\ProjectRequirementSubmission;
use Modules\Project\ProjectManagement\Services\ProjectProcedureService;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\Shared\ResourceShare\Models\ResourceShare;
use Modules\User\Models\User;

class AttachmentRequestChartsTest extends BaseAttendanceReportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->schemaReady()) {
            $this->markTestSkipped('Attachment request charts schema is not migrated.');
        }

        Storage::fake('public');
    }

    public function test_charts_route_is_resolved_before_attachment_request_id_route(): void
    {
        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests/charts')
            ->assertOk()
            ->assertJsonStructure([
                'payload' => [
                    'attachment_requests',
                    'requirement_submissions',
                ],
            ]);
    }

    public function test_charts_return_separate_attachment_request_and_submission_sections(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $attachmentRequest = $this->createAttachmentRequest($project, $procedure, [
            'status' => AttachmentRequest::STATUS_PENDING,
        ]);
        $this->createAttachmentItem($attachmentRequest, [
            'status' => 'pending',
            'file_type' => 'application/pdf',
        ]);

        $requirement = $this->createRequirement($project, [
            'procedure_setting_id' => $procedure->procedure_setting_id,
        ]);
        $submission = $this->createRequirementSubmission($project, $requirement);
        $this->createProcess(
            ProjectRequirementSubmission::PROCESSABLE_TYPE,
            $submission->id,
            ProcessStatus::Completed->value,
            ['uploader_company_id' => (string) $this->company->id],
        );

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests/charts?project_id='.$project->id)
            ->assertOk()
            ->assertJsonPath('payload.attachment_requests.summary.total_requests', 1)
            ->assertJsonPath('payload.attachment_requests.summary.total_items', 1)
            ->assertJsonPath('payload.attachment_requests.status.chart_type', 'status')
            ->assertJsonPath('payload.attachment_requests.status.total', 1)
            ->assertJsonPath('payload.attachment_requests.file_type.data.0.code', 'application/pdf')
            ->assertJsonPath('payload.requirement_submissions.summary.total_submissions', 1)
            ->assertJsonPath('payload.requirement_submissions.summary.total_files', 1)
            ->assertJsonPath('payload.requirement_submissions.status.data.0.code', 'approved')
            ->assertJsonPath('payload.requirement_submissions.file_type.total', 1);
    }

    public function test_status_cross_filter_keeps_status_distribution_unfiltered(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);

        $pendingRequest = $this->createAttachmentRequest($project, $procedure, [
            'status' => AttachmentRequest::STATUS_PENDING,
            'date' => '2026-07-20',
        ]);
        $this->createAttachmentItem($pendingRequest, ['status' => 'pending']);

        $approvedRequest = $this->createAttachmentRequest($project, $procedure, [
            'status' => AttachmentRequest::STATUS_APPROVED,
            'date' => '2026-07-21',
        ]);
        $this->createAttachmentItem($approvedRequest, ['status' => 'approved']);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests/charts?project_id='.$project->id.'&status=pending')
            ->assertOk()
            ->assertJsonPath('payload.attachment_requests.summary.total_requests', 1)
            ->assertJsonPath('payload.attachment_requests.status.total', 2)
            ->assertJsonPath('payload.attachment_requests.trend.total', 1);
    }

    public function test_incoming_direction_uses_receiver_company_visibility_scope(): void
    {
        $project = $this->createProject();
        $receiverCompany = $this->createCompany();
        $restrictedCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $restrictedUser = User::factory()->create(['company_id' => $restrictedCompany->id]);

        $this->createAcceptedShare($project, $receiverCompany);
        $this->createAcceptedShare($project, $restrictedCompany);

        $procedure = $this->createProjectProcedure($project, [$receiverCompany->id]);

        $request = $this->createAttachmentRequest($project, $procedure, [
            'status' => AttachmentRequest::STATUS_PENDING,
        ]);
        $this->createAttachmentItem($request);
        $process = $this->createProcess(
            AttachmentRequest::PROCESSABLE_TYPE,
            $request->id,
            ProcessStatus::InProgress->value,
        );
        $this->createProcessStep($process, $restrictedUser);

        $this->actingAs($receiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->getJson('/api/v1/projects/attachment-requests/charts?project_id='.$project->id.'&direction=incoming')
            ->assertOk()
            ->assertJsonPath('payload.attachment_requests.summary.total_requests', 1)
            ->assertJsonPath('payload.attachment_requests.direction.data.0.code', 'incoming');

        $this->actingAs($restrictedUser, 'api')
            ->withHeader('X-Tenant', $restrictedCompany->id)
            ->getJson('/api/v1/projects/attachment-requests/charts?project_id='.$project->id.'&direction=incoming')
            ->assertOk()
            ->assertJsonPath('payload.attachment_requests.summary.total_requests', 0);
    }

    public function test_name_filter_targets_attachment_requests_and_hides_submissions(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $request = $this->createAttachmentRequest($project, $procedure, [
            'serial_number' => 'ATR-CHART-NAME',
        ]);
        $this->createAttachmentItem($request);

        $requirement = $this->createRequirement($project);
        $submission = $this->createRequirementSubmission($project, $requirement);
        $this->createProcess(
            ProjectRequirementSubmission::PROCESSABLE_TYPE,
            $submission->id,
            ProcessStatus::Completed->value,
            ['uploader_company_id' => (string) $this->company->id],
        );

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests/charts?project_id='.$project->id.'&name=CHART-NAME')
            ->assertOk()
            ->assertJsonPath('payload.attachment_requests.summary.total_requests', 1)
            ->assertJsonPath('payload.requirement_submissions.summary.total_submissions', 0);
    }

    public function test_charts_validate_supported_filter_values(): void
    {
        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/attachment-requests/charts?project_id=bad&direction=sideways&type=bad&status=bad&date_from=2026/07/20&date_to=bad')
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'project_id',
                'direction',
                'type',
                'status',
                'date_from',
                'date_to',
            ]);
    }

    private function schemaReady(): bool
    {
        return Schema::hasTable('attachment_requests')
            && Schema::hasTable('attachment_request_items')
            && Schema::hasTable('project_requirement_submissions')
            && Schema::hasTable('project_requirements')
            && Schema::hasTable('project_procedure_settings')
            && Schema::hasTable('projects')
            && Schema::hasTable('project_types')
            && Schema::hasTable('folders')
            && Schema::hasTable('procedure_settings')
            && Schema::hasTable('work_flows')
            && Schema::hasTable('processes')
            && Schema::hasTable('process_steps')
            && Schema::hasTable('media')
            && Schema::hasTable('resource_shares')
            && Schema::hasTable('project_procedure_setting_receiver_companies');
    }

    private function createProject(array $overrides = []): ProjectManagement
    {
        $projectTypeId = $this->projectTypeId();

        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->withoutGlobalScopes()->forceCreate(array_merge([
            'id' => (string) Str::uuid(),
            'project_type_id' => $projectTypeId,
            'sub_project_type_id' => $projectTypeId,
            'sub_sub_project_type_id' => $projectTypeId,
            'name' => 'Attachment Request Charts Test',
            'company_id' => $this->company->id,
            'status' => 1,
            'serial_number' => 'ARC-'.Str::upper(Str::random(6)),
        ], $overrides)));
    }

    private function projectTypeId(): int
    {
        return (int) ProjectType::query()->withoutGlobalScopes()->firstOrCreate(
            [
                'name' => 'Attachment Request Charts Test Type',
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
        array $receiverCompanyIds = [],
    ): ProjectProcedureSetting
    {
        $workFlow = WorkFlow::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'name' => 'charts_project_'.$project->id,
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
        ]);

        $parent = ProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'name' => 'Charts Project Procedures',
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
            'execute_type' => 'sequence',
            'is_active' => true,
            'work_flow_id' => $workFlow->id,
            'parent_id' => null,
        ]);

        $procedureSetting = ProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'name' => 'Charts Document Approval',
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
            'execute_type' => 'sequence',
            'is_active' => true,
            'work_flow_id' => $workFlow->id,
            'parent_id' => $parent->id,
            'sort_order' => 1,
        ]);

        $attachmentType = $this->createFolder($project, 'Charts Docs');

        $projectProcedure = ProjectProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'procedure_setting_id' => $procedureSetting->id,
            'attachment_type_id' => $attachmentType->id,
            'used_in_document_cycle' => true,
        ]);

        if ($receiverCompanyIds !== []) {
            $projectProcedure->receiverCompanies()->sync($receiverCompanyIds);
        }

        return $projectProcedure->refresh();
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

    private function createFolder(ProjectManagement $project, string $name): Folder
    {
        return Folder::query()->withoutGlobalScopes()->create([
            'name' => $name,
            'parent_id' => null,
            'project_id' => $project->id,
            'company_id' => $this->company->id,
            'access_type' => 'private',
            'status' => 1,
        ]);
    }

    private function createAttachmentRequest(
        ProjectManagement $project,
        ProjectProcedureSetting $procedure,
        array $overrides = [],
    ): AttachmentRequest {
        return AttachmentRequest::query()->withoutGlobalScopes()->forceCreate(array_merge([
            'id' => (string) Str::uuid(),
            'serial_number' => 'ATR-CHART-'.Str::upper(Str::random(6)),
            'name' => 'Charts Attachment Request',
            'date' => '2026-07-20',
            'project_id' => $project->id,
            'procedure_setting_id' => $procedure->procedure_setting_id,
            'sender_company_id' => $this->company->id,
            'status' => AttachmentRequest::STATUS_PENDING,
            'created_by_user_id' => $this->actor->id,
        ], $overrides));
    }

    private function createAttachmentItem(AttachmentRequest $request, array $overrides = []): void
    {
        $request->items()->create(array_merge([
            'file_name' => 'chart-file.pdf',
            'file_path' => null,
            'file_type' => 'application/pdf',
            'file_size' => 12000,
            'status' => 'pending',
        ], $overrides));
    }

    private function createRequirement(ProjectManagement $project, array $overrides = []): ProjectRequirement
    {
        return ProjectRequirement::query()->withoutGlobalScopes()->create(array_merge([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'requirement_code' => 'REQ-CHART-'.Str::upper(Str::random(6)),
            'required_document_name' => 'Charts requirement document',
            'document' => 'Charts requirement document',
            'document_type' => 'Technical Submittal',
            'specialization' => 'Electrical',
            'stage' => 'Owner',
            'sending_entity' => 'Consultant',
            'review_entity' => 'Contractor',
            'repetition' => ProjectRequirementRepetition::Once->value,
            'evaluation_status' => ProjectRequirementEvaluationStatus::UnderReview->value,
            'completion_percentage' => 70,
        ], $overrides));
    }

    private function createRequirementSubmission(
        ProjectManagement $project,
        ProjectRequirement $requirement,
    ): ProjectRequirementSubmission {
        $submission = ProjectRequirementSubmission::query()->withoutGlobalScopes()->create([
            'project_id' => $project->id,
            'project_requirement_id' => $requirement->id,
        ]);

        $submission
            ->addMedia(UploadedFile::fake()->create('requirement-file.pdf', 12, 'application/pdf'))
            ->toMediaCollection('files');

        return $submission;
    }

    private function createProcess(
        string $processableType,
        string $processableId,
        string $status,
        array $metadata = [],
    ): Process {
        return Process::query()->create([
            'processable_type' => $processableType,
            'processable_id' => $processableId,
            'execute_type' => 'sequence',
            'status' => $status,
            'sort_order' => 1,
            'template_snapshot' => [],
            'metadata' => $metadata,
        ]);
    }

    private function createProcessStep(Process $process, User $user): ProcessStep
    {
        return ProcessStep::query()->create([
            'process_id' => $process->id,
            'step_id' => null,
            'template_step_order' => 1,
            'assigned_user_id' => $user->id,
            'authorized_user_ids' => [$user->id],
            'status' => ProcessStepStatus::Pending->value,
        ]);
    }

    private function createCompany(array $overrides = []): Company
    {
        return Company::withoutEvents(fn () => Company::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Charts Receiver Company'],
            'user_name' => 'charts_receiver_'.Str::lower(Str::random(6)),
            'email' => 'charts-receiver-'.Str::lower(Str::random(6)).'@example.test',
            'phone' => '01000000000',
            'country_id' => $this->country->id,
            'company_type_id' => (string) Str::uuid(),
            'company_field_id' => (string) Str::uuid(),
            'registration_type_id' => (string) Str::uuid(),
            'general_manager_id' => (string) Str::uuid(),
            'is_active' => 1,
            'complete_data' => 1,
            'serial_no' => 'CHART-REC-'.Str::upper(Str::random(6)),
        ], $overrides)));
    }
}
