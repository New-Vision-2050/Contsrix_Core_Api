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
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\Project\ProjectManagement\Models\AttachmentRequest;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectProcedureSetting;
use Modules\Project\ProjectManagement\Services\ProjectProcedureService;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\Shared\ResourceShare\Models\ResourceShare;

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

    public function test_create_attachment_request_uses_request_receiver_and_derives_folder_data_from_selected_project_procedure(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);
        $receiverCompany = $this->createCompany();
        $this->createAcceptedShare($project, $receiverCompany);
        $ignoredAttachmentTypeId = (string) Str::uuid();

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests', [
                'name' => 'Shop Drawing Files',
                'date' => '2026-07-21',
                'project_id' => $project->id,
                'procedure_setting_id' => $procedure->procedure_setting_id,
                'receiver_company_id' => $receiverCompany->id,
                'attachment_type_id' => $ignoredAttachmentTypeId,
                'attachments' => [
                    UploadedFile::fake()->create('shop-drawing.pdf', 12, 'application/pdf'),
                ],
                'notes' => 'Created from selected project procedure',
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('payload.procedure_setting_id', $procedure->procedure_setting_id)
            ->assertJsonPath('payload.procedure_setting.id', $procedure->procedure_setting_id)
            ->assertJsonPath('payload.receiver_company.id', $receiverCompany->id)
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
            'receiver_company_id' => $receiverCompany->id,
            'notes' => 'Created from selected project procedure',
        ]);

        $this->assertTrue(
            AttachmentRequest::query()
                ->forReceiverCompany($receiverCompany->id)
                ->whereKey($response->json('payload.id'))
                ->exists()
        );
    }

    public function test_create_attachment_request_rejects_procedure_from_another_project(): void
    {
        $project = $this->createProject();
        $otherProject = $this->createProject();
        $otherProcedure = $this->createProjectProcedure($otherProject);
        $receiverCompany = $this->createCompany();
        $this->createAcceptedShare($project, $receiverCompany);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests', [
                'name' => 'Wrong Project Procedure',
                'date' => '2026-07-21',
                'project_id' => $project->id,
                'procedure_setting_id' => $otherProcedure->procedure_setting_id,
                'receiver_company_id' => $receiverCompany->id,
                'attachments' => [
                    UploadedFile::fake()->create('wrong-project.pdf', 12, 'application/pdf'),
                ],
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['procedure_setting_id']);
    }

    public function test_create_attachment_request_requires_receiver_company(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post('/api/v1/projects/attachment-requests', [
                'name' => 'Missing Receiver',
                'date' => '2026-07-21',
                'project_id' => $project->id,
                'procedure_setting_id' => $procedure->procedure_setting_id,
                'attachments' => [
                    UploadedFile::fake()->create('missing-receiver.pdf', 12, 'application/pdf'),
                ],
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receiver_company_id']);
    }

    private function schemaReady(): bool
    {
        return Schema::hasTable('attachment_requests')
            && Schema::hasColumn('attachment_requests', 'procedure_setting_id')
            && Schema::hasColumn('attachment_requests', 'receiver_company_id')
            && ! Schema::hasColumn('attachment_requests', 'attachment_type_id')
            && ! Schema::hasColumn('attachment_requests', 'attachment_sub_type_id')
            && ! Schema::hasColumn('attachment_requests', 'attachment_sub_sub_type_id')
            && Schema::hasTable('attachment_request_items')
            && Schema::hasTable('project_procedure_settings')
            && Schema::hasTable('folders')
            && Schema::hasTable('procedure_settings')
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
}
