<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectProcedureJobAttribute;
use Modules\Project\ProjectManagement\Services\ProjectProcedureService;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\Shared\ResourceShare\Models\ResourceShare;
use Spatie\Permission\Models\Permission as SpatiePermission;

class ProjectProcedureCrudTest extends BaseAttendanceReportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->projectProcedureTablesReady()) {
            $this->markTestSkipped('Project procedure schema is not migrated.');
        }

        $this->grantProjectProcedurePermissions();
    }

    public function test_project_procedure_crud_stores_core_data_and_metadata_separately(): void
    {
        $project = $this->createProject();
        $lookups = $this->createProcedureLookups($project);

        $createResponse = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/procedures", [
                'name' => 'Document Approval',
                'is_active' => true,
                'receiver_company_id' => $lookups['receiver_company']->id,
                'attachment_type_id' => $lookups['attachment_type']->id,
                'attachment_sub_type_id' => $lookups['attachment_sub_type']->id,
                'attachment_sub_sub_type_id' => $lookups['attachment_sub_sub_type']->id,
                'job_attribute_id' => $lookups['job_attribute']->id,
                'used_in_document_cycle' => true,
                'appears_in_archive_after_approval' => true,
                'appears_in_attachments_library' => false,
                'requires_asset_id' => true,
            ])
            ->assertOk();

        $procedureId = $createResponse->json('payload.id');

        $this->assertNotEmpty($procedureId);
        $this->assertSame('Document Approval', $createResponse->json('payload.name'));
        $this->assertSame($lookups['receiver_company']->id, $createResponse->json('payload.receiver_company.id'));
        $this->assertSame($lookups['attachment_type']->id, $createResponse->json('payload.attachment_type.id'));
        $this->assertSame($lookups['attachment_sub_type']->id, $createResponse->json('payload.attachment_sub_type.id'));
        $this->assertSame($lookups['attachment_sub_sub_type']->id, $createResponse->json('payload.attachment_sub_sub_type.id'));
        $this->assertSame($lookups['job_attribute']->id, $createResponse->json('payload.job_attribute.id'));
        $this->assertTrue($createResponse->json('payload.requires_asset_id'));
        $this->assertArrayNotHasKey('classification', $createResponse->json('payload'));
        $this->assertArrayNotHasKey('linked_folder', $createResponse->json('payload'));
        $this->assertArrayNotHasKey('document_nature', $createResponse->json('payload'));
        $this->assertArrayNotHasKey('classification_name', $createResponse->json('payload'));
        $this->assertArrayNotHasKey('linked_folder_name', $createResponse->json('payload'));
        $this->assertArrayNotHasKey('classification_code', $createResponse->json('payload'));
        $this->assertArrayNotHasKey('receiver_companies', $createResponse->json('payload'));

        $this->assertDatabaseHas('procedure_settings', [
            'id' => $procedureId,
            'company_id' => $this->company->id,
            'name' => 'Document Approval',
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('project_procedure_settings', [
            'project_id' => $project->id,
            'procedure_setting_id' => $procedureId,
            'receiver_company_id' => $lookups['receiver_company']->id,
            'attachment_type_id' => $lookups['attachment_type']->id,
            'attachment_sub_type_id' => $lookups['attachment_sub_type']->id,
            'attachment_sub_sub_type_id' => $lookups['attachment_sub_sub_type']->id,
            'job_attribute_id' => $lookups['job_attribute']->id,
            'requires_asset_id' => 1,
        ]);

        $this->assertFalse(Schema::hasColumn('procedure_settings', 'classification_name'));
        $this->assertTrue(Schema::hasColumn('project_procedure_settings', 'receiver_company_id'));
        $this->assertFalse(Schema::hasTable('project_procedure_receiver_companies'));
        $this->assertTrue(Schema::hasTable('project_procedure_job_attributes'));
        $this->assertFalse(Schema::hasColumn('project_procedure_settings', 'classification_name'));
        $this->assertFalse(Schema::hasColumn('project_procedure_settings', 'main_classification_id'));
        $this->assertFalse(Schema::hasColumn('project_procedure_settings', 'sub_classification_id'));
        $this->assertFalse(Schema::hasColumn('project_procedure_settings', 'sub_sub_classification_id'));
        $this->assertFalse(Schema::hasColumn('project_procedure_settings', 'linked_folder_id'));
        $this->assertFalse(Schema::hasColumn('project_procedure_settings', 'document_nature_id'));
        $this->assertFalse(Schema::hasColumn('procedure_settings', 'requires_asset_id'));

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/procedures")
            ->assertOk()
            ->assertJsonPath('payload.0.id', $procedureId)
            ->assertJsonPath('payload.0.attachment_type.id', $lookups['attachment_type']->id)
            ->assertJsonPath('payload.0.receiver_company.id', $lookups['receiver_company']->id);

        $updatedAttachmentType = $this->createFolder($project, 'Updated Project Docs');
        $updatedReceiverCompany = $this->createCompany([
            'name' => ['en' => 'Updated Receiver Company'],
            'serial_no' => 'PROC-REC-003',
        ]);
        $this->createAcceptedShare($project, $this->company, $updatedReceiverCompany);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson("/api/v1/projects/{$project->id}/procedures/{$procedureId}", [
                'name' => 'Updated Document Approval',
                'is_active' => false,
                'receiver_company_id' => $updatedReceiverCompany->id,
                'attachment_type_id' => $updatedAttachmentType->id,
                'attachment_sub_type_id' => null,
                'attachment_sub_sub_type_id' => null,
                'used_in_document_cycle' => false,
            ])
            ->assertOk()
            ->assertJsonPath('payload.name', 'Updated Document Approval')
            ->assertJsonPath('payload.is_active', false)
            ->assertJsonPath('payload.attachment_type.id', $updatedAttachmentType->id)
            ->assertJsonPath('payload.attachment_sub_type', null)
            ->assertJsonPath('payload.attachment_sub_sub_type', null)
            ->assertJsonPath('payload.receiver_company.id', $updatedReceiverCompany->id);

        $this->assertDatabaseHas('procedure_settings', [
            'id' => $procedureId,
            'name' => 'Updated Document Approval',
            'is_active' => 0,
        ]);

        $this->assertDatabaseHas('project_procedure_settings', [
            'project_id' => $project->id,
            'procedure_setting_id' => $procedureId,
            'receiver_company_id' => $updatedReceiverCompany->id,
            'attachment_type_id' => $updatedAttachmentType->id,
            'attachment_sub_type_id' => null,
            'attachment_sub_sub_type_id' => null,
            'used_in_document_cycle' => 0,
        ]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/procedures/{$procedureId}")
            ->assertOk()
            ->assertJsonPath('payload.id', $procedureId)
            ->assertJsonPath('payload.procedure_setting.type', ProjectProcedureService::PROCEDURE_TYPE);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->deleteJson("/api/v1/projects/{$project->id}/procedures/{$procedureId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('procedure_settings', [
            'id' => $procedureId,
        ]);

        $this->assertDatabaseMissing('project_procedure_settings', [
            'procedure_setting_id' => $procedureId,
        ]);
    }

    private function projectProcedureTablesReady(): bool
    {
        return Schema::hasTable('project_procedure_settings')
            && Schema::hasTable('project_procedure_job_attributes')
            && Schema::hasTable('folders')
            && Schema::hasTable('procedure_settings')
            && Schema::hasTable('resource_shares')
            && Schema::hasTable('work_flows');
    }

    private function createProject(): ProjectManagement
    {
        $projectTypeId = $this->projectTypeId();

        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->withoutGlobalScopes()->forceCreate([
            'id' => (string) Str::uuid(),
            'project_type_id' => $projectTypeId,
            'sub_project_type_id' => $projectTypeId,
            'sub_sub_project_type_id' => $projectTypeId,
            'name' => 'Project Procedure Test',
            'company_id' => $this->company->id,
            'status' => 1,
            'serial_number' => 'PROC-'.Str::upper(Str::random(6)),
        ]));
    }

    private function projectTypeId(): int
    {
        return (int) ProjectType::query()->withoutGlobalScopes()->firstOrCreate(
            [
                'name' => 'Project Procedure Test Type',
                'company_id' => $this->company->id,
            ],
            [
                'is_created' => true,
                'is_have_schema' => false,
                'is_active' => true,
            ],
        )->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function createProcedureLookups(ProjectManagement $project): array
    {
        $receiverCompany = $this->createCompany([
            'name' => ['en' => 'Receiver Company'],
            'serial_no' => 'PROC-REC-001',
        ]);
        $this->createAcceptedShare($project, $this->company, $receiverCompany);

        $attachmentType = $this->createFolder($project, 'Project Docs');
        $attachmentSubType = $this->createFolder($project, 'Design Docs', $attachmentType->id);
        $attachmentSubSubType = $this->createFolder($project, 'Issued For Approval', $attachmentSubType->id);

        $jobAttribute = ProjectProcedureJobAttribute::query()->firstOrCreate(
            ['code' => 'engineer'],
            [
                'name' => 'Engineer',
                'is_active' => true,
            ],
        );

        return [
            'receiver_company' => $receiverCompany,
            'attachment_type' => $attachmentType,
            'attachment_sub_type' => $attachmentSubType,
            'attachment_sub_sub_type' => $attachmentSubSubType,
            'job_attribute' => $jobAttribute,
        ];
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCompany(array $overrides = []): Company
    {
        return Company::withoutEvents(fn () => Company::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Project Procedure Company'],
            'user_name' => 'project_procedure_'.Str::lower(Str::random(6)),
            'email' => 'procedure-company@example.test',
            'phone' => '01000000000',
            'country_id' => $this->country->id,
            'company_type_id' => (string) Str::uuid(),
            'company_field_id' => (string) Str::uuid(),
            'registration_type_id' => (string) Str::uuid(),
            'general_manager_id' => (string) Str::uuid(),
            'is_active' => 1,
            'complete_data' => 1,
            'serial_no' => 'PROC-'.Str::upper(Str::random(6)),
        ], $overrides)));
    }

    private function createAcceptedShare(
        ProjectManagement $project,
        Company $ownerCompany,
        Company $receiverCompany
    ): ResourceShare {
        return ResourceShare::query()->create([
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
        ]);
    }

    private function grantProjectProcedurePermissions(): void
    {
        setPermissionsTeamId($this->company->id);

        $permissions = [
            Permission::PROJECT_MANAGEMENT_VIEW(),
            Permission::PROJECT_MANAGEMENT_UPDATE(),
        ];

        foreach ($permissions as $permission) {
            SpatiePermission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'api'],
                ['name' => $permission, 'guard_name' => 'api', 'company_id' => $this->company->id],
            );
        }

        $this->actor->givePermissionTo($permissions);
    }
}
