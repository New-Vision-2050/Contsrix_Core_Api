<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\ProcedureSetting\Models\ProcedureSettingStepActionTaker;
use Modules\ProcedureSetting\Models\ProcedureSettingStepConcernedManagementHierarchy;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectProcedureJobAttribute;
use Modules\Project\ProjectManagement\Models\ProjectProcedureSetting;
use Modules\Project\ProjectManagement\Services\ProjectProcedureService;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\Shared\ResourceShare\Models\ResourceShare;
use Modules\User\Models\User;
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

    public function test_project_procedure_job_attributes_lookup_lists_active_options(): void
    {
        $activeCode = 'lookup_architect_'.Str::lower(Str::random(6));
        $inactiveCode = 'hidden_lookup_role_'.Str::lower(Str::random(6));

        $active = ProjectProcedureJobAttribute::query()->create([
            'name' => 'Lookup Architect',
            'code' => $activeCode,
            'is_active' => true,
        ]);

        $inactive = ProjectProcedureJobAttribute::query()->create([
            'name' => 'Hidden Lookup Role',
            'code' => $inactiveCode,
            'is_active' => false,
        ]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/procedure-settings/job-attributes')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $active->id,
                'name' => 'Lookup Architect',
                'code' => $activeCode,
                'is_active' => true,
            ])
            ->assertJsonMissing([
                'id' => $inactive->id,
            ]);
    }

    public function test_project_procedure_crud_stores_core_data_and_metadata_separately(): void
    {
        $project = $this->createProject();
        $lookups = $this->createProcedureLookups($project);

        $createResponse = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/procedure-settings/internal-procedures', [
                'project_id' => $project->id,
                'name' => 'Document Approval',
                'is_active' => true,
                'receiver_company_id' => (string) Str::uuid(),
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
        $childProcedure = ProcedureSetting::query()
            ->withoutGlobalScopes()
            ->whereKey($procedureId)
            ->firstOrFail();
        $workFlowId = $childProcedure->work_flow_id;
        $parentId = $childProcedure->parent_id;

        $this->assertNotEmpty($procedureId);
        $this->assertNotEmpty($parentId);
        $this->assertSame(ProjectProcedureService::PROCEDURE_TYPE, $createResponse->json('payload.type'));
        $this->assertSame('Document Approval', $createResponse->json('payload.name'));
        $this->assertSame($parentId, $createResponse->json('payload.parent_id'));
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
        $this->assertArrayNotHasKey('receiver_company', $createResponse->json('payload'));
        $this->assertSame([], $createResponse->json('payload.receiver_company_ids'));
        $this->assertSame([], $createResponse->json('payload.receiver_companies'));

        $this->assertDatabaseHas('procedure_settings', [
            'id' => $procedureId,
            'company_id' => $this->company->id,
            'name' => 'Document Approval',
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
            'is_active' => 1,
            'work_flow_id' => $workFlowId,
            'parent_id' => $parentId,
        ]);

        $this->assertDatabaseHas('procedure_settings', [
            'id' => $parentId,
            'company_id' => $this->company->id,
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
            'work_flow_id' => $workFlowId,
            'parent_id' => null,
        ]);

        $this->assertDatabaseHas('work_flows', [
            'id' => $workFlowId,
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
        ]);

        $this->assertDatabaseHas('project_procedure_settings', [
            'project_id' => $project->id,
            'procedure_setting_id' => $procedureId,
            'attachment_type_id' => $lookups['attachment_type']->id,
            'attachment_sub_type_id' => $lookups['attachment_sub_type']->id,
            'attachment_sub_sub_type_id' => $lookups['attachment_sub_sub_type']->id,
            'job_attribute_id' => $lookups['job_attribute']->id,
            'requires_asset_id' => 1,
        ]);

        $this->assertFalse(Schema::hasColumn('procedure_settings', 'classification_name'));
        $this->assertFalse(Schema::hasColumn('project_procedure_settings', 'receiver_company_id'));
        $this->assertTrue(Schema::hasTable('project_procedure_setting_receiver_companies'));
        $this->assertTrue(Schema::hasTable('project_procedure_job_attributes'));
        $this->assertFalse(Schema::hasColumn('project_procedure_settings', 'classification_name'));
        $this->assertFalse(Schema::hasColumn('project_procedure_settings', 'main_classification_id'));
        $this->assertFalse(Schema::hasColumn('project_procedure_settings', 'sub_classification_id'));
        $this->assertFalse(Schema::hasColumn('project_procedure_settings', 'sub_sub_classification_id'));
        $this->assertFalse(Schema::hasColumn('project_procedure_settings', 'linked_folder_id'));
        $this->assertFalse(Schema::hasColumn('project_procedure_settings', 'document_nature_id'));
        $this->assertFalse(Schema::hasColumn('procedure_settings', 'requires_asset_id'));

        $listResponse = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/procedure-settings/internal-procedures?project_id={$project->id}")
            ->assertOk()
            ->assertJsonPath('payload.0.id', $procedureId)
            ->assertJsonPath('payload.0.parent_id', $parentId)
            ->assertJsonPath('payload.0.attachment_type.id', $lookups['attachment_type']->id);

        $this->assertArrayNotHasKey('receiver_company', $listResponse->json('payload.0'));
        $this->assertSame([], $listResponse->json('payload.0.receiver_company_ids'));

        $updatedAttachmentType = $this->createFolder($project, 'Updated Project Docs');

        $updateResponse = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson("/api/v1/procedure-settings/{$procedureId}", [
                'project_id' => $project->id,
                'name' => 'Updated Document Approval',
                'is_active' => false,
                'receiver_company_id' => (string) Str::uuid(),
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
            ->assertJsonPath('payload.attachment_sub_sub_type', null);

        $this->assertArrayNotHasKey('receiver_company', $updateResponse->json('payload'));
        $this->assertSame([], $updateResponse->json('payload.receiver_company_ids'));

        $this->assertDatabaseHas('procedure_settings', [
            'id' => $procedureId,
            'name' => 'Updated Document Approval',
            'is_active' => 0,
        ]);

        $this->assertDatabaseHas('project_procedure_settings', [
            'project_id' => $project->id,
            'procedure_setting_id' => $procedureId,
            'attachment_type_id' => $updatedAttachmentType->id,
            'attachment_sub_type_id' => null,
            'attachment_sub_sub_type_id' => null,
            'used_in_document_cycle' => 0,
        ]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/procedure-settings/{$procedureId}?project_id={$project->id}")
            ->assertOk()
            ->assertJsonPath('payload.id', $procedureId)
            ->assertJsonPath('payload.parent_id', $parentId)
            ->assertJsonPath('payload.procedure_setting.type', ProjectProcedureService::PROCEDURE_TYPE);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->deleteJson("/api/v1/procedure-settings/{$procedureId}?project_id={$project->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('procedure_settings', [
            'id' => $procedureId,
        ]);

        $this->assertDatabaseMissing('project_procedure_settings', [
            'procedure_setting_id' => $procedureId,
        ]);
    }

    public function test_project_internal_procedure_routes_ignore_metadata_linked_to_global_workflow(): void
    {
        $project = $this->createProject();

        $globalWorkFlow = WorkFlow::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'project_id' => null,
            'name' => 'global_project_procedure_'.Str::lower(Str::random(6)),
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
        ]);

        $globalParent = ProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'name' => 'Global Project Procedure Parent',
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
            'execute_type' => 'sequence',
            'work_flow_id' => $globalWorkFlow->id,
            'sort_order' => 1,
        ]);

        $globalChild = ProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'name' => 'Global Linked Internal Procedure',
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
            'execute_type' => 'sequence',
            'work_flow_id' => $globalWorkFlow->id,
            'parent_id' => $globalParent->id,
            'sort_order' => 2,
        ]);

        ProjectProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'procedure_setting_id' => $globalChild->id,
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/procedure-settings/internal-procedures?project_id={$project->id}")
            ->assertOk();

        $ids = collect($response->json('payload'))->pluck('id')->all();
        $this->assertNotContains($globalChild->id, $ids);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/procedure-settings/{$globalChild->id}?project_id={$project->id}")
            ->assertNotFound();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson("/api/v1/procedure-settings/{$globalChild->id}", [
                'project_id' => $project->id,
                'name' => 'Should Not Update Global Linked Procedure',
            ])
            ->assertNotFound();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->deleteJson("/api/v1/procedure-settings/{$globalChild->id}?project_id={$project->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('procedure_settings', [
            'id' => $globalChild->id,
            'name' => 'Global Linked Internal Procedure',
            'work_flow_id' => $globalWorkFlow->id,
            'parent_id' => $globalParent->id,
        ]);
    }

    public function test_project_internal_procedure_project_routes_are_removed(): void
    {
        $project = $this->createProject();
        $procedureId = (string) Str::uuid();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/internal-procedures")
            ->assertNotFound();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/internal-procedures", [
                'name' => 'Removed Route Procedure',
            ])
            ->assertNotFound();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/internal-procedures/{$procedureId}")
            ->assertNotFound();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson("/api/v1/projects/{$project->id}/internal-procedures/{$procedureId}", [
                'name' => 'Removed Route Procedure',
            ])
            ->assertNotFound();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->deleteJson("/api/v1/projects/{$project->id}/internal-procedures/{$procedureId}")
            ->assertNotFound();

        $this->assertDatabaseMissing('procedure_settings', [
            'name' => 'Removed Route Procedure',
        ]);
    }

    public function test_project_internal_procedure_store_uses_existing_project_parent_workflow(): void
    {
        $project = $this->createProject();

        $workFlow = WorkFlow::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'name' => 'explicit_project_procedure_'.Str::lower(Str::random(6)),
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
        ]);

        $parent = ProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'name' => 'Explicit Parent',
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
            'execute_type' => 'sequence',
            'work_flow_id' => $workFlow->id,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/procedure-settings/internal-procedures', [
                'project_id' => $project->id,
                'name' => 'Nested Internal Procedure',
                'is_active' => true,
            ])
            ->assertOk();

        $procedureId = $response->json('payload.id');

        $this->assertDatabaseHas('procedure_settings', [
            'id' => $procedureId,
            'name' => 'Nested Internal Procedure',
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
            'work_flow_id' => $workFlow->id,
            'parent_id' => $parent->id,
        ]);

        $this->assertDatabaseHas('project_procedure_settings', [
            'project_id' => $project->id,
            'procedure_setting_id' => $procedureId,
        ]);
    }

    public function test_project_procedure_list_is_accessible_from_shared_company(): void
    {
        $project = $this->createProject();
        $procedureSetting = $this->createProjectProcedure($project);
        $receiverCompany = $this->createReceiverCompany();

        $this->createAcceptedShare($project, $receiverCompany);

        setPermissionsTeamId($receiverCompany->id);
        $permissions = [
            Permission::PROJECT_MANAGEMENT_VIEW(),
        ];
        foreach ($permissions as $permission) {
            SpatiePermission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'api'],
                ['name' => $permission, 'guard_name' => 'api', 'company_id' => $receiverCompany->id],
            );
        }
        $this->actor->givePermissionTo($permissions);
        setPermissionsTeamId($this->company->id);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->getJson("/api/v1/procedure-settings/internal-procedures?type=project_procedure&project_id={$project->id}")
            ->assertOk();

        $ids = collect($response->json('payload'))->pluck('id')->all();

        $this->assertContains($procedureSetting->id, $ids);
    }

    public function test_project_procedure_visibility_can_be_limited_to_selected_receiver_companies(): void
    {
        $project = $this->createProject();
        $firstReceiverCompany = $this->createReceiverCompany(['serial_no' => 'PROC-VIS-A']);
        $secondReceiverCompany = $this->createReceiverCompany(['serial_no' => 'PROC-VIS-B']);
        $firstReceiverUser = User::factory()->create(['company_id' => $firstReceiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $secondReceiverCompany->id]);

        $this->createAcceptedShare($project, $firstReceiverCompany);
        $this->createAcceptedShare($project, $secondReceiverCompany);
        $this->grantProjectProcedurePermissionsForCompany($firstReceiverCompany, $firstReceiverUser);
        $this->grantProjectProcedurePermissionsForCompany($secondReceiverCompany, $secondReceiverUser);

        $unrestrictedId = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/procedure-settings/internal-procedures', [
                'project_id' => $project->id,
                'name' => 'Visible To All Shared Companies',
            ])
            ->assertOk()
            ->assertJsonPath('payload.receiver_company_ids', [])
            ->json('payload.id');

        $restrictedId = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/procedure-settings/internal-procedures', [
                'project_id' => $project->id,
                'name' => 'Visible To First Receiver',
                'receiver_company_ids' => [$firstReceiverCompany->id],
            ])
            ->assertOk()
            ->assertJsonPath('payload.receiver_company_ids.0', $firstReceiverCompany->id)
            ->assertJsonPath('payload.receiver_companies.0.id', $firstReceiverCompany->id)
            ->json('payload.id');

        $this->assertDatabaseHas('project_procedure_setting_receiver_companies', [
            'company_id' => $firstReceiverCompany->id,
        ]);

        $ownerIds = collect($this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/procedure-settings/internal-procedures?type=project_procedure&project_id={$project->id}")
            ->assertOk()
            ->json('payload'))->pluck('id')->all();

        $firstReceiverIds = collect($this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $firstReceiverCompany->id)
            ->getJson("/api/v1/procedure-settings/internal-procedures?type=project_procedure&project_id={$project->id}")
            ->assertOk()
            ->json('payload'))->pluck('id')->all();

        $secondReceiverIds = collect($this->actingAs($secondReceiverUser, 'api')
            ->withHeader('X-Tenant', $secondReceiverCompany->id)
            ->getJson("/api/v1/procedure-settings/internal-procedures?type=project_procedure&project_id={$project->id}")
            ->assertOk()
            ->json('payload'))->pluck('id')->all();

        $this->assertContains($unrestrictedId, $ownerIds);
        $this->assertContains($restrictedId, $ownerIds);
        $this->assertContains($unrestrictedId, $firstReceiverIds);
        $this->assertContains($restrictedId, $firstReceiverIds);
        $this->assertContains($unrestrictedId, $secondReceiverIds);
        $this->assertNotContains($restrictedId, $secondReceiverIds);
    }

    public function test_project_procedure_receiver_company_ids_must_be_accepted_project_shares(): void
    {
        $project = $this->createProject();
        $receiverCompany = $this->createReceiverCompany();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/procedure-settings/internal-procedures', [
                'project_id' => $project->id,
                'name' => 'Invalid Receiver Procedure',
                'receiver_company_ids' => [$receiverCompany->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receiver_company_ids']);
    }

    public function test_project_procedure_create_can_clone_steps_from_source_procedure_independently(): void
    {
        $project = $this->createProject();
        $receiverCompany = $this->createReceiverCompany();
        $this->createAcceptedShare($project, $receiverCompany);
        $management = $this->createManagementHierarchy();

        $sourceId = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/procedure-settings/internal-procedures', [
                'project_id' => $project->id,
                'name' => 'jj',
            ])
            ->assertOk()
            ->json('payload.id');

        $sourceStep = ProcedureSettingStep::query()->withoutGlobalScopes()->create([
            'procedure_setting_id' => $sourceId,
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'name' => 'Source review',
            'forms' => 'approve',
            'is_approve' => true,
            'is_return_with_notes' => true,
            'requires_approval_within_period' => true,
            'approval_within_days' => 2,
            'approval_within_hours' => 3,
            'notify_by_email' => true,
            'notify_by_sms' => true,
            'step_order' => 7,
            'action_taker_type' => 'specific_user',
            'receiver_company_ids' => [$receiverCompany->id],
            'project_employee_ids' => [$this->actor->id],
        ]);
        ProcedureSettingStepActionTaker::query()->create([
            'procedure_setting_step_id' => $sourceStep->id,
            'user_id' => $this->actor->id,
            'company_id' => $this->company->id,
        ]);
        ProcedureSettingStepConcernedManagementHierarchy::query()->create([
            'procedure_setting_step_id' => $sourceStep->id,
            'management_hierarchy_id' => $management->id,
            'company_id' => $this->company->id,
        ]);

        $targetId = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/procedure-settings/internal-procedures', [
                'project_id' => $project->id,
                'name' => 'kk',
                'source_procedure_setting_id' => $sourceId,
            ])
            ->assertOk()
            ->assertJsonPath('payload.name', 'kk')
            ->json('payload.id');

        $sourceSteps = ProcedureSettingStep::query()
            ->withoutGlobalScopes()
            ->where('procedure_setting_id', $sourceId)
            ->get();
        $targetSteps = ProcedureSettingStep::query()
            ->withoutGlobalScopes()
            ->where('procedure_setting_id', $targetId)
            ->get();

        $this->assertCount(1, $sourceSteps);
        $this->assertCount(1, $targetSteps);

        $targetStep = $targetSteps->first();
        $this->assertNotSame($sourceStep->id, $targetStep->id);
        $this->assertSame('Source review', $targetStep->name);
        $this->assertSame(7, $targetStep->step_order);
        $this->assertTrue($targetStep->is_return_with_notes);
        $this->assertSame([$receiverCompany->id], $targetStep->receiver_company_ids);
        $this->assertSame([(string) $this->actor->id], array_map('strval', $targetStep->project_employee_ids));

        $this->assertDatabaseHas('procedure_setting_step_action_takers', [
            'procedure_setting_step_id' => $targetStep->id,
            'user_id' => $this->actor->id,
        ]);
        $this->assertDatabaseHas('procedure_setting_step_concerned_management_hierarchies', [
            'procedure_setting_step_id' => $targetStep->id,
            'management_hierarchy_id' => $management->id,
        ]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/procedure-settings/{$targetId}/steps/{$targetStep->id}", [
                'name' => 'Target review edited',
            ])
            ->assertOk()
            ->assertJsonPath('payload.name', 'Target review edited');

        $this->assertSame(
            'Source review',
            ProcedureSettingStep::query()->withoutGlobalScopes()->findOrFail($sourceStep->id)->name,
        );
    }

    public function test_project_procedure_step_can_use_receiver_company_action_takers(): void
    {
        $project = $this->createProject();
        $procedureSetting = $this->createProjectProcedure($project);
        $firstReceiverCompany = $this->createReceiverCompany(['serial_no' => 'PROC-REC-A']);
        $secondReceiverCompany = $this->createReceiverCompany(['serial_no' => 'PROC-REC-B']);

        $this->createAcceptedShare($project, $firstReceiverCompany);
        $this->createAcceptedShare($project, $secondReceiverCompany);

        $createResponse = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/procedure-settings/{$procedureSetting->id}/steps", [
                'name' => 'Receiver company review',
                'forms' => 'approve',
                'is_approve' => true,
                'action_taker_type' => 'receiver_company',
                'receiver_company_ids' => [$firstReceiverCompany->id],
            ])
            ->assertOk()
            ->assertJsonPath('payload.action_taker_type', 'receiver_company')
            ->assertJsonPath('payload.action_taker_type_label', 'Receiver Company')
            ->assertJsonPath('payload.receiver_company_ids.0', $firstReceiverCompany->id)
            ->assertJsonPath('payload.receiver_companies.0.id', $firstReceiverCompany->id);

        $stepId = $createResponse->json('payload.id');
        $step = ProcedureSettingStep::query()->findOrFail($stepId);
        $this->assertSame([$firstReceiverCompany->id], $step->receiver_company_ids);

        $updateResponse = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/procedure-settings/{$procedureSetting->id}/steps/{$stepId}", [
                'action_taker_type' => 'receiver_company',
                'receiver_company_ids' => [$secondReceiverCompany->id],
            ])
            ->assertOk()
            ->assertJsonPath('payload.receiver_company_ids.0', $secondReceiverCompany->id)
            ->assertJsonPath('payload.receiver_companies.0.id', $secondReceiverCompany->id);

        $this->assertCount(1, $updateResponse->json('payload.receiver_companies'));
        $this->assertSame(
            [$secondReceiverCompany->id],
            ProcedureSettingStep::query()->findOrFail($stepId)->receiver_company_ids,
        );
    }

    public function test_receiver_company_ids_do_not_require_distinct_values(): void
    {
        $project = $this->createProject();
        $procedureSetting = $this->createProjectProcedure($project);
        $receiverCompany = $this->createReceiverCompany();

        $this->createAcceptedShare($project, $receiverCompany);

        $receiverCompanyIds = [
            $receiverCompany->id,
            $receiverCompany->id,
        ];

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/procedure-settings/{$procedureSetting->id}/steps", [
                'name' => 'Duplicated receiver company review',
                'forms' => 'approve',
                'is_approve' => true,
                'action_taker_type' => 'receiver_company',
                'receiver_company_ids' => $receiverCompanyIds,
            ])
            ->assertOk()
            ->assertJsonPath('payload.receiver_company_ids.0', $receiverCompany->id)
            ->assertJsonCount(1, 'payload.receiver_company_ids');

        $step = ProcedureSettingStep::query()->findOrFail($response->json('payload.id'));

        $this->assertSame($receiverCompanyIds, $step->receiver_company_ids);
    }

    public function test_receiver_company_step_resolves_project_from_project_procedure_link(): void
    {
        $project = $this->createProject();
        $procedureSetting = $this->createProjectProcedureWithGlobalWorkflow($project);
        $receiverCompany = $this->createReceiverCompany();

        $this->createAcceptedShare($project, $receiverCompany);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/procedure-settings/{$procedureSetting->id}/steps", [
                'name' => 'Receiver company review',
                'forms' => 'approve',
                'is_approve' => true,
                'action_taker_type' => 'receiver_company',
                'receiver_company_ids' => [$receiverCompany->id],
            ])
            ->assertOk()
            ->assertJsonPath('payload.project_id', $project->id)
            ->assertJsonPath('payload.receiver_company_ids.0', $receiverCompany->id);

        $this->assertDatabaseHas('procedure_setting_steps', [
            'id' => $response->json('payload.id'),
            'procedure_setting_id' => $procedureSetting->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_receiver_company_action_taker_requires_receiver_company_ids_only(): void
    {
        $project = $this->createProject();
        $procedureSetting = $this->createProjectProcedure($project);
        $receiverCompany = $this->createReceiverCompany();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/procedure-settings/{$procedureSetting->id}/steps", [
                'name' => 'Receiver company review',
                'forms' => 'approve',
                'is_approve' => true,
                'action_taker_type' => 'receiver_company',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receiver_company_ids']);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/procedure-settings/{$procedureSetting->id}/steps", [
                'name' => 'Receiver company review',
                'forms' => 'approve',
                'is_approve' => true,
                'action_taker_type' => 'receiver_company',
                'receiver_company_ids' => [$receiverCompany->id],
            ])
            ->assertOk()
            ->assertJsonPath('payload.receiver_company_ids.0', $receiverCompany->id);

        $this->assertSame(
            [$receiverCompany->id],
            ProcedureSettingStep::query()->findOrFail($response->json('payload.id'))->receiver_company_ids,
        );

        $step = ProcedureSettingStep::query()->create([
            'procedure_setting_id' => $procedureSetting->id,
            'company_id' => $this->company->id,
            'name' => 'Existing specific user review',
            'forms' => 'approve',
            'is_approve' => true,
            'action_taker_type' => 'specific_user',
        ]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/procedure-settings/{$procedureSetting->id}/steps/{$step->id}", [
                'action_taker_type' => 'receiver_company',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receiver_company_ids']);
    }

    private function projectProcedureTablesReady(): bool
    {
        return Schema::hasTable('project_procedure_settings')
            && Schema::hasTable('project_procedure_setting_receiver_companies')
            && Schema::hasTable('project_procedure_job_attributes')
            && Schema::hasTable('folders')
            && Schema::hasTable('procedure_settings')
            && Schema::hasTable('procedure_setting_steps')
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

    private function createProjectProcedure(ProjectManagement $project): ProcedureSetting
    {
        $workFlow = WorkFlow::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'name' => 'receiver_company_workflow_'.Str::lower(Str::random(6)),
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
        ]);

        $parent = ProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'name' => 'Receiver Company Parent',
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
            'execute_type' => 'sequence',
            'is_active' => true,
            'work_flow_id' => $workFlow->id,
            'parent_id' => null,
            'sort_order' => 1,
        ]);

        $procedureSetting = ProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'name' => 'Receiver Company Procedure',
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
            'execute_type' => 'sequence',
            'is_active' => true,
            'work_flow_id' => $workFlow->id,
            'parent_id' => $parent->id,
            'sort_order' => 1,
        ]);

        ProjectProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'procedure_setting_id' => $procedureSetting->id,
        ]);

        return $procedureSetting;
    }

    private function createProjectProcedureWithGlobalWorkflow(ProjectManagement $project): ProcedureSetting
    {
        $workFlow = WorkFlow::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'project_id' => null,
            'name' => 'receiver_company_global_workflow_'.Str::lower(Str::random(6)),
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
        ]);

        $parent = ProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'name' => 'Receiver Company Global Parent',
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
            'execute_type' => 'sequence',
            'is_active' => true,
            'work_flow_id' => $workFlow->id,
            'parent_id' => null,
            'sort_order' => 1,
        ]);

        $procedureSetting = ProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'name' => 'Receiver Company Global Procedure',
            'type' => ProjectProcedureService::PROCEDURE_TYPE,
            'execute_type' => 'sequence',
            'is_active' => true,
            'work_flow_id' => $workFlow->id,
            'parent_id' => $parent->id,
            'sort_order' => 1,
        ]);

        ProjectProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'procedure_setting_id' => $procedureSetting->id,
        ]);

        return $procedureSetting;
    }

    private function createReceiverCompany(array $overrides = []): Company
    {
        return Company::withoutEvents(fn () => Company::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Procedure Receiver Company'],
            'user_name' => 'procedure_receiver_'.Str::lower(Str::random(6)),
            'email' => 'procedure-receiver-'.Str::lower(Str::random(6)).'@example.test',
            'phone' => '01000000000',
            'country_id' => $this->country->id,
            'company_type_id' => (string) Str::uuid(),
            'company_field_id' => (string) Str::uuid(),
            'registration_type_id' => (string) Str::uuid(),
            'general_manager_id' => (string) Str::uuid(),
            'is_active' => 1,
            'complete_data' => 1,
            'serial_no' => 'PROC-REC-'.Str::upper(Str::random(6)),
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

    private function createManagementHierarchy(): ManagementHierarchy
    {
        return ManagementHierarchy::query()->withoutGlobalScopes()->create([
            'name' => 'Procedure Concerned Management',
            'company_id' => $this->company->id,
            'type' => 'management',
        ]);
    }

    private function grantProjectProcedurePermissions(): void
    {
        $this->grantProjectProcedurePermissionsForCompany($this->company);
    }

    private function grantProjectProcedurePermissionsForCompany(Company $company, ?User $user = null): void
    {
        setPermissionsTeamId($company->id);
        $user ??= $this->actor;

        $permissions = [
            Permission::PROJECT_MANAGEMENT_VIEW(),
            Permission::PROJECT_MANAGEMENT_UPDATE(),
        ];

        foreach ($permissions as $permission) {
            SpatiePermission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'api'],
                ['name' => $permission, 'guard_name' => 'api', 'company_id' => $company->id],
            );
        }

        $user->givePermissionTo($permissions);
        setPermissionsTeamId($this->company->id);
    }
}
