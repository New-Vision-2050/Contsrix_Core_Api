<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\Process\Models\Process;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\ProcedureSetting\Models\ProcedureSettingStepActionTaker;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementEvaluationStatus;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementRepetition;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectProcedureSetting;
use Modules\Project\ProjectManagement\Models\ProjectRequirement;
use Modules\Project\ProjectManagement\Models\ProjectRequirementSubmission;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\Shared\ResourceShare\Models\ResourceShare;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission as SpatiePermission;

class ProjectRequirementSubmissionTest extends BaseAttendanceReportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->schemaReady()) {
            $this->markTestSkipped('Project requirement submission schema is not migrated.');
        }

        Storage::fake('public');
        $this->grantProjectRequirementPermissions();
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_requirement_list_and_show_include_upload_status_for_current_period(): void
    {
        $this->travelTo('2026-07-20 10:00:00');

        [$project, $requirement] = $this->projectRequirementWithReceiver([
            'repetition' => ProjectRequirementRepetition::Daily->value,
            'repetition_interval_type' => 'day',
        ]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements")
            ->assertOk()
            ->assertJsonPath('payload.0.id', $requirement->id)
            ->assertJsonPath('payload.0.upload_status.can_upload', true)
            ->assertJsonPath('payload.0.upload_status.current_period_key', 'daily:2026-07-20');

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements/{$requirement->id}")
            ->assertOk()
            ->assertJsonPath('payload.upload_status.can_upload', true)
            ->assertJsonPath('payload.upload_status.disabled_reason', null);
    }

    public function test_once_submission_creates_files_and_blocks_duplicate_upload(): void
    {
        $this->travelTo('2026-07-20 10:00:00');

        [$project, $requirement] = $this->projectRequirementWithReceiver([
            'repetition' => ProjectRequirementRepetition::Once->value,
        ]);

        $response = $this->postSubmission($project, $requirement)
            ->assertOk()
            ->assertJsonPath('payload.project_id', $project->id)
            ->assertJsonPath('payload.project_requirement_id', $requirement->id)
            ->assertJsonPath('payload.files.0.file_name', 'requirement-file.pdf');

        $this->assertDatabaseHas('project_requirement_submissions', [
            'id' => $response->json('payload.id'),
            'project_id' => $project->id,
            'project_requirement_id' => $requirement->id,
        ]);

        $this->assertSubmissionHasFile($response->json('payload.id'), 'requirement-file.pdf');

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements/{$requirement->id}")
            ->assertOk()
            ->assertJsonPath('payload.upload_status.can_upload', false)
            ->assertJsonPath('payload.upload_status.disabled_reason', 'already_submitted')
            ->assertJsonPath('payload.upload_status.latest_submission.id', $response->json('payload.id'))
            ->assertJsonPath('payload.upload_status.latest_submission.files.0.file_name', 'requirement-file.pdf');

        $this->postSubmission($project, $requirement)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['requirement']);
    }

    public function test_daily_submission_reopens_on_next_calendar_day(): void
    {
        $this->travelTo('2026-07-20 10:00:00');

        [$project, $requirement] = $this->projectRequirementWithReceiver([
            'repetition' => ProjectRequirementRepetition::Daily->value,
            'repetition_interval_type' => 'day',
        ]);

        $this->postSubmission($project, $requirement)->assertOk();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements/{$requirement->id}")
            ->assertOk()
            ->assertJsonPath('payload.upload_status.can_upload', false)
            ->assertJsonPath('payload.upload_status.disabled_reason', 'already_submitted');

        $this->travelTo('2026-07-21 09:00:00');

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements/{$requirement->id}")
            ->assertOk()
            ->assertJsonPath('payload.upload_status.can_upload', true)
            ->assertJsonPath('payload.upload_status.current_period_key', 'daily:2026-07-21');
    }

    public function test_weekly_submission_blocks_current_iso_week_and_reopens_next_week(): void
    {
        $this->travelTo('2026-07-20 10:00:00');

        [$project, $requirement] = $this->projectRequirementWithReceiver([
            'repetition' => ProjectRequirementRepetition::Weekly->value,
            'repetition_interval_type' => 'week',
        ]);

        $this->postSubmission($project, $requirement)
            ->assertOk()
            ->assertJsonPath('payload.files.0.file_name', 'requirement-file.pdf');

        $this->travelTo('2026-07-22 10:00:00');

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements/{$requirement->id}")
            ->assertOk()
            ->assertJsonPath('payload.upload_status.can_upload', false)
            ->assertJsonPath('payload.upload_status.disabled_reason', 'already_submitted');

        $this->travelTo('2026-07-27 09:00:00');

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements/{$requirement->id}")
            ->assertOk()
            ->assertJsonPath('payload.upload_status.can_upload', true)
            ->assertJsonPath('payload.upload_status.current_period_key', 'weekly:2026-W31');
    }

    public function test_monthly_submission_reopens_next_calendar_month(): void
    {
        $this->travelTo('2026-07-23 10:00:00');

        [$project, $requirement] = $this->projectRequirementWithReceiver([
            'repetition' => ProjectRequirementRepetition::Monthly->value,
            'repetition_interval_type' => 'month',
        ]);

        $this->postSubmission($project, $requirement)
            ->assertOk()
            ->assertJsonPath('payload.files.0.file_name', 'requirement-file.pdf');

        $this->travelTo('2026-07-31 10:00:00');

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements/{$requirement->id}")
            ->assertOk()
            ->assertJsonPath('payload.upload_status.can_upload', false);

        $this->travelTo('2026-08-01 09:00:00');

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements/{$requirement->id}")
            ->assertOk()
            ->assertJsonPath('payload.upload_status.can_upload', true)
            ->assertJsonPath('payload.upload_status.current_period_key', 'monthly:2026-08');
    }

    public function test_repeat_days_disable_upload_until_configured_weekday(): void
    {
        $this->travelTo('2026-07-21 10:00:00');

        [$project, $requirement] = $this->projectRequirementWithReceiver([
            'repetition' => ProjectRequirementRepetition::Weekly->value,
            'repetition_interval_type' => 'week',
            'repeat_days' => ['wednesday'],
        ]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements/{$requirement->id}")
            ->assertOk()
            ->assertJsonPath('payload.upload_status.can_upload', false)
            ->assertJsonPath('payload.upload_status.disabled_reason', 'outside_repeat_days');

        $this->postSubmission($project, $requirement)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['requirement']);

        $this->travelTo('2026-07-22 10:00:00');

        $this->postSubmission($project, $requirement)
            ->assertOk()
            ->assertJsonPath('payload.files.0.file_name', 'requirement-file.pdf');
    }

    public function test_assigned_company_can_upload_and_unassigned_company_is_rejected(): void
    {
        $this->travelTo('2026-07-20 10:00:00');

        [$project, $requirement, $receiverCompany] = $this->projectRequirementWithReceiver([
            'repetition' => ProjectRequirementRepetition::Daily->value,
            'repetition_interval_type' => 'day',
        ]);
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);

        $this->actingAs($receiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->post($this->submissionUrl($project, $requirement), [
                'files' => [
                    UploadedFile::fake()->create('receiver-upload.pdf', 12, 'application/pdf'),
                ],
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.project_id', $project->id)
            ->assertJsonPath('payload.project_requirement_id', $requirement->id)
            ->assertJsonPath('payload.files.0.file_name', 'receiver-upload.pdf');

        $this->assertDatabaseHas('project_requirement_submissions', [
            'project_id' => $project->id,
            'project_requirement_id' => $requirement->id,
        ]);

        $unassignedCompany = $this->createCompany();
        $unassignedUser = User::factory()->create(['company_id' => $unassignedCompany->id]);

        $this->actingAs($unassignedUser, 'api')
            ->withHeader('X-Tenant', $unassignedCompany->id)
            ->post($this->submissionUrl($project, $requirement), [
                'files' => [
                    UploadedFile::fake()->create('blocked.pdf', 12, 'application/pdf'),
                ],
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['requirement']);
    }

    public function test_submission_starts_sequence_project_procedure_workflow(): void
    {
        [$project, $requirement, $receiverCompany] = $this->projectRequirementWithReceiver();
        $procedure = $this->createProjectProcedure($project);
        $this->attachProcedureToRequirement($requirement, $procedure);
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createProcedureStep($project, $procedure, $receiverUser, 1);
        $this->createProcedureStep($project, $procedure, $receiverUser, 2);

        $response = $this->postSubmission($project, $requirement)
            ->assertOk()
            ->assertJsonPath('payload.process.status', ProcessStatus::InProgress->value)
            ->assertJsonCount(1, 'payload.process_steps');

        $submissionId = $response->json('payload.id');

        $this->assertDatabaseHas('processes', [
            'processable_id' => $submissionId,
            'processable_type' => ProjectRequirementSubmission::PROCESSABLE_TYPE,
            'status' => ProcessStatus::InProgress->value,
        ]);

        $this->assertDatabaseHas('process_steps', [
            'assigned_user_id' => $receiverUser->id,
            'template_step_order' => 1,
            'status' => ProcessStepStatus::Pending->value,
        ]);
    }

    public function test_submission_starts_parallel_project_procedure_workflow(): void
    {
        [$project, $requirement, $receiverCompany] = $this->projectRequirementWithReceiver();
        $procedure = $this->createProjectProcedure($project, ['execute_type' => 'parallel']);
        $this->attachProcedureToRequirement($requirement, $procedure);
        $firstReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createProcedureStep($project, $procedure, $firstReceiverUser, 1);
        $this->createProcedureStep($project, $procedure, $secondReceiverUser, 2);

        $response = $this->postSubmission($project, $requirement)
            ->assertOk()
            ->assertJsonPath('payload.process.execute_type', 'parallel')
            ->assertJsonCount(2, 'payload.process_steps');

        $process = Process::query()
            ->where('processable_id', $response->json('payload.id'))
            ->where('processable_type', ProjectRequirementSubmission::PROCESSABLE_TYPE)
            ->firstOrFail();

        $this->assertSame(2, $process->steps()->where('status', ProcessStepStatus::Pending->value)->count());
    }

    public function test_submission_receiver_company_workflow_step_uses_requirement_context(): void
    {
        [$project, $requirement, $receiverCompany] = $this->projectRequirementWithReceiver();
        $procedure = $this->createProjectProcedure($project);
        $this->attachProcedureToRequirement($requirement, $procedure);
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createReceiverCompanyProcedureStep($project, $procedure, 1);

        $response = $this->postSubmission($project, $requirement)
            ->assertOk()
            ->assertJsonPath('payload.process.status', ProcessStatus::InProgress->value)
            ->assertJsonCount(1, 'payload.process_steps');

        $process = Process::query()
            ->where('processable_id', $response->json('payload.id'))
            ->where('processable_type', ProjectRequirementSubmission::PROCESSABLE_TYPE)
            ->firstOrFail();

        $step = $process->steps()->firstOrFail();
        $this->assertContains((string) $receiverUser->id, $step->authorized_user_ids);
    }

    public function test_submission_without_resolvable_workflow_steps_keeps_upload_flow_unchanged(): void
    {
        [$project, $requirement] = $this->projectRequirementWithReceiver();
        $procedure = $this->createProjectProcedure($project);
        $this->attachProcedureToRequirement($requirement, $procedure);

        $response = $this->postSubmission($project, $requirement)
            ->assertOk()
            ->assertJsonPath('payload.process', null)
            ->assertJsonPath('payload.workflow', null)
            ->assertJsonPath('payload.files.0.file_name', 'requirement-file.pdf');

        $this->assertDatabaseMissing('processes', [
            'processable_id' => $response->json('payload.id'),
            'processable_type' => ProjectRequirementSubmission::PROCESSABLE_TYPE,
        ]);
    }

    public function test_submission_workflow_approve_delivers_to_archive_library(): void
    {
        [$project, $requirement, $receiverCompany] = $this->projectRequirementWithReceiver();
        $procedure = $this->createProjectProcedure($project);
        $this->attachProcedureToRequirement($requirement, $procedure);
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createProcedureStep($project, $procedure, $receiverUser, 1);

        $submissionId = $this->postSubmission($project, $requirement)
            ->assertOk()
            ->json('payload.id');

        $this->actingAs($receiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->postJson("/api/v1/projects/{$project->id}/requirements/{$requirement->id}/submissions/{$submissionId}/approve")
            ->assertOk()
            ->assertJsonPath('payload.process.status', ProcessStatus::Completed->value);

        $this->assertDatabaseHas('processes', [
            'processable_id' => $submissionId,
            'processable_type' => ProjectRequirementSubmission::PROCESSABLE_TYPE,
            'status' => ProcessStatus::Completed->value,
        ]);
    }

    public function test_submission_history_returns_workflow_fields(): void
    {
        [$project, $requirement, $receiverCompany] = $this->projectRequirementWithReceiver();
        $procedure = $this->createProjectProcedure($project);
        $this->attachProcedureToRequirement($requirement, $procedure);
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $this->createProcedureStep($project, $procedure, $receiverUser, 1);

        $submissionId = $this->postSubmission($project, $requirement)
            ->assertOk()
            ->json('payload.id');

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson($this->submissionUrl($project, $requirement))
            ->assertOk()
            ->assertJsonPath('payload.0.id', $submissionId)
            ->assertJsonPath('payload.0.process.status', ProcessStatus::InProgress->value)
            ->assertJsonCount(1, 'payload.0.process_steps');
    }

    public function test_submission_history_returns_file_preview(): void
    {
        $this->travelTo('2026-07-20 10:00:00');

        [$project, $requirement] = $this->projectRequirementWithReceiver();

        $submissionId = $this->postSubmission($project, $requirement)
            ->assertOk()
            ->json('payload.id');

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson($this->submissionUrl($project, $requirement))
            ->assertOk()
            ->assertJsonPath('payload.0.id', $submissionId)
            ->assertJsonPath('payload.0.files.0.file_name', 'requirement-file.pdf');
    }

    private function schemaReady(): bool
    {
        return Schema::hasTable('project_requirement_submissions')
            && Schema::hasTable('project_requirements')
            && Schema::hasTable('project_requirement_receiver_companies')
            && Schema::hasTable('media')
            && Schema::hasTable('projects')
            && Schema::hasTable('project_types')
            && Schema::hasTable('resource_shares')
            && Schema::hasTable('procedure_settings')
            && Schema::hasTable('procedure_setting_steps')
            && Schema::hasTable('procedure_setting_step_action_takers')
            && Schema::hasTable('project_procedure_settings')
            && Schema::hasTable('work_flows')
            && Schema::hasTable('processes')
            && Schema::hasTable('process_steps');
    }

    /**
     * @return array{0: ProjectManagement, 1: ProjectRequirement, 2: Company}
     */
    private function projectRequirementWithReceiver(array $requirementOverrides = []): array
    {
        $project = $this->createProject();
        $receiverCompany = $this->createCompany();

        $this->createAcceptedShare($project, $this->company, $receiverCompany);

        $requirement = $this->createRequirement($project, $requirementOverrides);
        $requirement->receiverCompanies()->sync([$receiverCompany->id]);

        return [$project, $requirement->refresh()->load('receiverCompanies'), $receiverCompany];
    }

    private function postSubmission(ProjectManagement $project, ProjectRequirement $requirement)
    {
        return $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post($this->submissionUrl($project, $requirement), [
                'files' => [
                    UploadedFile::fake()->create('requirement-file.pdf', 12, 'application/pdf'),
                ],
            ], ['Accept' => 'application/json']);
    }

    private function assertSubmissionHasFile(string $submissionId, string $fileName): void
    {
        $this->assertDatabaseHas('media', [
            'model_type' => ProjectRequirementSubmission::class,
            'model_id' => $submissionId,
            'collection_name' => 'files',
            'file_name' => $fileName,
        ]);
    }

    private function submissionUrl(ProjectManagement $project, ProjectRequirement $requirement): string
    {
        return "/api/v1/projects/{$project->id}/requirements/{$requirement->id}/submissions";
    }

    private function createProject(array $overrides = []): ProjectManagement
    {
        $projectTypeId = $this->projectTypeId();

        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->withoutGlobalScopes()->forceCreate(array_merge([
            'id' => (string) Str::uuid(),
            'project_type_id' => $projectTypeId,
            'sub_project_type_id' => $projectTypeId,
            'sub_sub_project_type_id' => $projectTypeId,
            'name' => 'Project Requirement Submission Test',
            'company_id' => $this->company->id,
            'status' => 1,
            'serial_number' => 'REQ-SUB-'.Str::upper(Str::random(6)),
        ], $overrides)));
    }

    private function projectTypeId(): int
    {
        return (int) ProjectType::query()->withoutGlobalScopes()->firstOrCreate(
            [
                'name' => 'Project Requirement Submission Test Type',
                'company_id' => $this->company->id,
            ],
            [
                'is_created' => true,
                'is_have_schema' => false,
                'is_active' => true,
            ],
        )->id;
    }

    private function createRequirement(ProjectManagement $project, array $overrides = []): ProjectRequirement
    {
        return ProjectRequirement::query()->withoutGlobalScopes()->create(array_merge([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'requirement_code' => 'REQ-SUB-'.Str::upper(Str::random(6)),
            'required_document_name' => 'Stamped approval document',
            'document' => 'Shop drawing - lv panel',
            'document_type' => 'Technical Submittal',
            'specialization' => 'Electrical',
            'stage' => 'Owner',
            'sending_entity' => 'Consultant',
            'review_entity' => 'Contractor',
            'repetition' => ProjectRequirementRepetition::Once->value,
            'repetition_interval_type' => null,
            'evaluation_status' => ProjectRequirementEvaluationStatus::UnderReview->value,
            'resulting_document' => 'KDC-VD-SDR-15',
            'completion_percentage' => 70,
        ], $overrides));
    }

    private function createProjectProcedure(ProjectManagement $project, array $overrides = []): ProcedureSetting
    {
        $workFlow = WorkFlow::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'name' => 'project_requirement_submission_'.$project->id,
            'type' => ProjectProcedureSetting::PROCEDURE_TYPE,
        ]);

        $parent = ProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'name' => 'Project Requirement Submission Procedures',
            'type' => ProjectProcedureSetting::PROCEDURE_TYPE,
            'execute_type' => $overrides['execute_type'] ?? 'sequence',
            'is_active' => true,
            'work_flow_id' => $workFlow->id,
            'parent_id' => null,
        ]);

        $procedure = ProcedureSetting::query()->withoutGlobalScopes()->create(array_merge([
            'company_id' => $this->company->id,
            'name' => 'Requirement Submission Approval',
            'type' => ProjectProcedureSetting::PROCEDURE_TYPE,
            'execute_type' => 'sequence',
            'is_active' => true,
            'work_flow_id' => $workFlow->id,
            'parent_id' => $parent->id,
            'sort_order' => 1,
        ], $overrides));

        ProjectProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'procedure_setting_id' => $procedure->id,
            'used_in_document_cycle' => true,
        ]);

        return $procedure;
    }

    private function attachProcedureToRequirement(
        ProjectRequirement $requirement,
        ProcedureSetting $procedure
    ): ProjectRequirement {
        $requirement->forceFill(['procedure_setting_id' => $procedure->id])->save();

        return $requirement->refresh()->load(['receiverCompanies', 'procedureSetting']);
    }

    private function createProcedureStep(
        ProjectManagement $project,
        ProcedureSetting $procedure,
        User $user,
        int $order
    ): ProcedureSettingStep {
        $step = ProcedureSettingStep::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'procedure_setting_id' => $procedure->id,
            'project_id' => $project->id,
            'name' => 'Requirement Submission Workflow Step '.$order,
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
        ProjectManagement $project,
        ProcedureSetting $procedure,
        int $order
    ): ProcedureSettingStep {
        return ProcedureSettingStep::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'procedure_setting_id' => $procedure->id,
            'project_id' => $project->id,
            'name' => 'Requirement Receiver Company Workflow Step '.$order,
            'forms' => 'approve',
            'is_approve' => true,
            'step_order' => $order,
            'action_taker_type' => 'receiver_company',
        ]);
    }

    private function createCompany(array $overrides = []): Company
    {
        return Company::withoutEvents(fn () => Company::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Project Requirement Submission Receiver'],
            'user_name' => 'project_requirement_submission_receiver_'.Str::lower(Str::random(6)),
            'email' => 'project-requirement-submission-receiver-'.Str::lower(Str::random(6)).'@example.test',
            'phone' => '01000000000',
            'country_id' => $this->country->id,
            'company_type_id' => (string) Str::uuid(),
            'company_field_id' => (string) Str::uuid(),
            'registration_type_id' => (string) Str::uuid(),
            'general_manager_id' => (string) Str::uuid(),
            'is_active' => 1,
            'complete_data' => 1,
            'serial_no' => 'REQ-SUB-REC-'.Str::upper(Str::random(6)),
        ], $overrides)));
    }

    private function createAcceptedShare(
        ProjectManagement $project,
        Company $ownerCompany,
        Company $receiverCompany,
        array $overrides = []
    ): ResourceShare {
        return ResourceShare::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'shareable_type' => ProjectManagement::class,
            'shareable_id' => $project->id,
            'owner_company_id' => $ownerCompany->id,
            'shared_with_company_id' => $receiverCompany->id,
            'status' => 'accepted',
            'schema_ids' => [1, 2],
            'shared_by_user_id' => $this->actor->id,
            'responded_by_user_id' => $this->actor->id,
            'responded_at' => now(),
        ], $overrides));
    }

    private function grantProjectRequirementPermissions(
        ?User $user = null,
        ?Company $company = null,
        ?array $permissions = null
    ): void {
        $user ??= $this->actor;
        $company ??= $this->company;
        $permissions ??= [
            Permission::PROJECT_REQUIREMENT_LIST(),
            Permission::PROJECT_REQUIREMENT_VIEW(),
            Permission::PROJECT_REQUIREMENT_UPDATE(),
        ];

        setPermissionsTeamId($company->id);

        foreach ($permissions as $permission) {
            SpatiePermission::query()->firstOrCreate(
                ['name' => $permission, 'guard_name' => 'api'],
                ['id' => (string) Str::uuid(), 'company_id' => $this->company->id],
            );
        }

        $user->givePermissionTo($permissions);
    }
}
