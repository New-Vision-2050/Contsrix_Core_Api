<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Company\CompanyCore\Models\Company;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementEvaluationStatus;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementRepetition;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectProcedureSetting;
use Modules\Project\ProjectManagement\Models\ProjectRequirement;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\Shared\ResourceShare\Models\ResourceShare;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission as SpatiePermission;

class ProjectRequirementCrudTest extends BaseAttendanceReportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->schemaReady()) {
            $this->markTestSkipped('Project requirements schema is not migrated.');
        }

        $this->grantProjectRequirementPermissions();
    }

    public function test_user_can_list_project_requirements_with_pagination_and_summary_counts(): void
    {
        $project = $this->createProject();
        $approved = $this->createRequirement($project, [
            'requirement_code' => 'REQ-LIST-001',
            'evaluation_status' => ProjectRequirementEvaluationStatus::Approved->value,
        ]);
        $inProgress = $this->createRequirement($project, [
            'requirement_code' => 'REQ-LIST-002',
            'evaluation_status' => ProjectRequirementEvaluationStatus::InProgress->value,
        ]);
        $this->createRequirement($project, [
            'requirement_code' => 'REQ-LIST-003',
            'evaluation_status' => ProjectRequirementEvaluationStatus::Rejected->value,
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements?per_page=2")
            ->assertOk()
            ->assertJsonPath('pagination.result_count', 3)
            ->assertJsonPath('pagination.last_page', 2)
            ->assertJsonPath('summary.total', 3)
            ->assertJsonPath('summary.approved', 1)
            ->assertJsonPath('summary.in_progress', 1)
            ->assertJsonPath('summary.rejected', 1)
            ->assertJsonPath('summary.pending_acceptance', 0)
            ->assertJsonPath('summary.under_review', 0);

        $ids = collect($response->json('payload'))->pluck('id');

        $this->assertTrue($ids->contains($approved->id) || $ids->contains($inProgress->id));
    }

    public function test_user_can_filter_and_search_project_requirements(): void
    {
        $project = $this->createProject();
        $receiverCompany = $this->createCompany();
        $procedure = $this->createProjectProcedure($project, 'Document Approval');
        $otherProcedure = $this->createProjectProcedure($project, 'Inspection Review');
        $matching = $this->createRequirement($project, [
            'requirement_code' => 'NTF-0001',
            'required_document_name' => 'Approved shop drawing',
            'document' => 'Shop drawing - lv panel',
            'procedure_setting_id' => $procedure->procedure_setting_id,
            'document_type' => 'Technical Submittal',
            'specialization' => 'Electrical',
            'stage' => 'Owner',
            'sending_entity' => 'Consultant',
        ]);
        $this->createRequirement($project, [
            'requirement_code' => 'NTF-0002',
            'required_document_name' => 'Mechanical inspection',
            'document' => 'Pump room report',
            'procedure_setting_id' => $otherProcedure->procedure_setting_id,
            'document_type' => 'Inspection Report',
            'specialization' => 'Mechanical',
            'stage' => 'Contractor',
            'sending_entity' => 'Contractor',
        ]);
        $this->createAcceptedShare($project, $this->company, $receiverCompany);
        $matching->receiverCompanies()->sync([$receiverCompany->id]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/'.$project->id.'/requirements?search=lv%20panel'
                .'&procedure_setting_id='.$procedure->procedure_setting_id
                .'&document_type=Technical%20Submittal'
                .'&specialization=Electrical'
                .'&stage=Owner'
                .'&sending_entity=Consultant'
                .'&receiver_company_id='.$receiverCompany->id)
            ->assertOk()
            ->assertJsonPath('pagination.result_count', 1)
            ->assertJsonPath('payload.0.id', $matching->id);
    }

    public function test_user_can_create_single_project_requirement(): void
    {
        $project = $this->createProject();
        $procedure = $this->createProjectProcedure($project, 'Document Approval');

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/requirements", [
                'requirement_code' => 'NTF-1001',
                'required_document_name' => 'Stamped approval document',
                'document' => 'Shop drawing - lv panel',
                'procedure_setting_id' => $procedure->procedure_setting_id,
                'document_type' => 'Technical Submittal',
                'specialization' => 'Electrical',
                'repetition' => ProjectRequirementRepetition::Daily->value,
                'repetition_interval_type' => 'day',
                'repeat_days' => ['saturday', 'monday'],
            ])
            ->assertOk()
            ->assertJsonPath('payload.requirement_code', 'NTF-1001')
            ->assertJsonPath('payload.procedure_setting_id', $procedure->procedure_setting_id)
            ->assertJsonPath('payload.procedure_setting.id', $procedure->procedure_setting_id)
            ->assertJsonPath('payload.procedure_setting.name', 'Document Approval')
            ->assertJsonPath('payload.evaluation_status', ProjectRequirementEvaluationStatus::default());

        $this->assertArrayNotHasKey('document_type_id', $response->json('payload'));
        $this->assertArrayNotHasKey('document_type_lookup', $response->json('payload'));

        $this->assertDatabaseHas('project_requirements', [
            'id' => $response->json('payload.id'),
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'requirement_code' => 'NTF-1001',
            'procedure_setting_id' => $procedure->procedure_setting_id,
            'repetition' => ProjectRequirementRepetition::Daily->value,
        ]);
    }

    public function test_user_can_create_project_requirement_with_receiver_companies(): void
    {
        $project = $this->createProject();
        $firstReceiverCompany = $this->createCompany(['serial_no' => 'REQ-REC-001']);
        $secondReceiverCompany = $this->createCompany(['serial_no' => 'REQ-REC-002']);

        $this->createAcceptedShare($project, $this->company, $firstReceiverCompany);
        $this->createAcceptedShare($project, $this->company, $secondReceiverCompany);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/requirements", $this->requirementPayload('NTF-RECV-001', [
                'receiver_company_ids' => [
                    $firstReceiverCompany->id,
                    $secondReceiverCompany->id,
                ],
            ]))
            ->assertOk()
            ->assertJsonPath('payload.requirement_code', 'NTF-RECV-001')
            ->assertJsonCount(2, 'payload.receiver_company_ids')
            ->assertJsonCount(2, 'payload.receiver_companies');

        $receiverCompanyIds = collect($response->json('payload.receiver_company_ids'));

        $this->assertTrue($receiverCompanyIds->contains($firstReceiverCompany->id));
        $this->assertTrue($receiverCompanyIds->contains($secondReceiverCompany->id));
        $this->assertDatabaseHas('project_requirement_receiver_companies', [
            'project_requirement_id' => $response->json('payload.id'),
            'company_id' => $firstReceiverCompany->id,
        ]);
        $this->assertDatabaseHas('project_requirement_receiver_companies', [
            'project_requirement_id' => $response->json('payload.id'),
            'company_id' => $secondReceiverCompany->id,
        ]);
    }

    public function test_project_requirement_rejects_receiver_companies_without_accepted_project_share(): void
    {
        $project = $this->createProject();
        $receiverCompany = $this->createCompany();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/requirements", $this->requirementPayload('NTF-RECV-INVALID-001', [
                'receiver_company_ids' => [$receiverCompany->id],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['requirements.0.receiver_company_ids']);

        $this->createAcceptedShare($project, $this->company, $receiverCompany, ['status' => 'pending']);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/requirements", $this->requirementPayload('NTF-RECV-INVALID-002', [
                'receiver_company_ids' => [$receiverCompany->id],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['requirements.0.receiver_company_ids']);
    }

    public function test_user_can_create_multiple_project_requirements(): void
    {
        $project = $this->createProject();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/requirements", [
                'requirements' => [
                    $this->requirementPayload('NTF-2001', [
                        'document' => 'Drawing A',
                        'repetition' => ProjectRequirementRepetition::Once->value,
                    ]),
                    $this->requirementPayload('NTF-2002', [
                        'document' => 'Drawing B',
                        'repetition' => ProjectRequirementRepetition::Weekly->value,
                        'repetition_interval_type' => 'week',
                        'repeat_days' => ['sunday'],
                    ]),
                ],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'payload')
            ->assertJsonPath('payload.0.requirement_code', 'NTF-2001')
            ->assertJsonPath('payload.1.requirement_code', 'NTF-2002');

        $this->assertDatabaseHas('project_requirements', [
            'project_id' => $project->id,
            'requirement_code' => 'NTF-2001',
        ]);
        $this->assertDatabaseHas('project_requirements', [
            'project_id' => $project->id,
            'requirement_code' => 'NTF-2002',
        ]);
    }

    public function test_user_can_show_project_requirement(): void
    {
        $project = $this->createProject();
        $requirement = $this->createRequirement($project, [
            'requirement_code' => 'NTF-SHOW-001',
        ]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements/{$requirement->id}")
            ->assertOk()
            ->assertJsonPath('payload.id', $requirement->id)
            ->assertJsonPath('payload.requirement_code', 'NTF-SHOW-001');
    }

    public function test_user_can_update_project_requirement(): void
    {
        $project = $this->createProject();
        $requirement = $this->createRequirement($project, [
            'requirement_code' => 'NTF-UPD-001',
            'completion_percentage' => 20,
        ]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson("/api/v1/projects/{$project->id}/requirements/{$requirement->id}", [
                'required_document_name' => 'Updated required document',
                'evaluation_status' => ProjectRequirementEvaluationStatus::Approved->value,
                'completion_percentage' => 100,
                'resulting_document' => 'KDC-VD-SDR-17',
            ])
            ->assertOk()
            ->assertJsonPath('payload.required_document_name', 'Updated required document')
            ->assertJsonPath('payload.evaluation_status', ProjectRequirementEvaluationStatus::Approved->value)
            ->assertJsonPath('payload.completion_percentage', 100);

        $this->assertDatabaseHas('project_requirements', [
            'id' => $requirement->id,
            'required_document_name' => 'Updated required document',
            'evaluation_status' => ProjectRequirementEvaluationStatus::Approved->value,
            'completion_percentage' => 100,
            'resulting_document' => 'KDC-VD-SDR-17',
        ]);
    }

    public function test_user_can_replace_and_clear_project_requirement_receiver_companies(): void
    {
        $project = $this->createProject();
        $firstReceiverCompany = $this->createCompany();
        $secondReceiverCompany = $this->createCompany();
        $thirdReceiverCompany = $this->createCompany();
        $requirement = $this->createRequirement($project, [
            'requirement_code' => 'NTF-RECV-UPD-001',
        ]);

        foreach ([$firstReceiverCompany, $secondReceiverCompany, $thirdReceiverCompany] as $receiverCompany) {
            $this->createAcceptedShare($project, $this->company, $receiverCompany);
        }

        $requirement->receiverCompanies()->sync([$firstReceiverCompany->id]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson("/api/v1/projects/{$project->id}/requirements/{$requirement->id}", [
                'receiver_company_ids' => [
                    $secondReceiverCompany->id,
                    $thirdReceiverCompany->id,
                ],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'payload.receiver_company_ids');

        $receiverCompanyIds = collect($response->json('payload.receiver_company_ids'));

        $this->assertFalse($receiverCompanyIds->contains($firstReceiverCompany->id));
        $this->assertTrue($receiverCompanyIds->contains($secondReceiverCompany->id));
        $this->assertTrue($receiverCompanyIds->contains($thirdReceiverCompany->id));
        $this->assertDatabaseMissing('project_requirement_receiver_companies', [
            'project_requirement_id' => $requirement->id,
            'company_id' => $firstReceiverCompany->id,
        ]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson("/api/v1/projects/{$project->id}/requirements/{$requirement->id}", [
                'receiver_company_ids' => [],
            ])
            ->assertOk()
            ->assertJsonPath('payload.receiver_company_ids', [])
            ->assertJsonPath('payload.receiver_companies', []);

        $this->assertDatabaseMissing('project_requirement_receiver_companies', [
            'project_requirement_id' => $requirement->id,
            'company_id' => $secondReceiverCompany->id,
        ]);
        $this->assertDatabaseMissing('project_requirement_receiver_companies', [
            'project_requirement_id' => $requirement->id,
            'company_id' => $thirdReceiverCompany->id,
        ]);
    }

    public function test_user_can_delete_project_requirement(): void
    {
        $project = $this->createProject();
        $requirement = $this->createRequirement($project);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->deleteJson("/api/v1/projects/{$project->id}/requirements/{$requirement->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('project_requirements', [
            'id' => $requirement->id,
        ]);
    }

    public function test_project_requirement_routes_do_not_expose_another_projects_requirements(): void
    {
        $project = $this->createProject();
        $otherProject = $this->createProject(['name' => 'Other Project']);
        $otherRequirement = $this->createRequirement($otherProject, [
            'requirement_code' => 'NTF-OTHER-001',
        ]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements/{$otherRequirement->id}")
            ->assertNotFound();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson("/api/v1/projects/{$project->id}/requirements/{$otherRequirement->id}", [
                'required_document_name' => 'Should Not Update',
            ])
            ->assertNotFound();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->deleteJson("/api/v1/projects/{$project->id}/requirements/{$otherRequirement->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('project_requirements', [
            'id' => $otherRequirement->id,
            'project_id' => $otherProject->id,
            'required_document_name' => $otherRequirement->required_document_name,
        ]);
    }

    public function test_receiver_company_can_list_and_show_only_assigned_project_requirements(): void
    {
        $project = $this->createProject();
        $firstReceiverCompany = $this->createCompany();
        $secondReceiverCompany = $this->createCompany();
        $firstReceiverUser = User::factory()->create(['company_id' => $firstReceiverCompany->id]);
        $procedure = $this->createProjectProcedure($project, 'Receiver Visible Procedure');
        $assignedRequirement = $this->createRequirement($project, [
            'requirement_code' => 'NTF-RECV-LIST-001',
            'procedure_setting_id' => $procedure->procedure_setting_id,
        ]);
        $otherReceiverRequirement = $this->createRequirement($project, [
            'requirement_code' => 'NTF-RECV-LIST-002',
        ]);
        $this->createRequirement($project, [
            'requirement_code' => 'NTF-RECV-LIST-003',
        ]);

        $this->createAcceptedShare($project, $this->company, $firstReceiverCompany);
        $this->createAcceptedShare($project, $this->company, $secondReceiverCompany);
        $assignedRequirement->receiverCompanies()->sync([$firstReceiverCompany->id]);
        $otherReceiverRequirement->receiverCompanies()->sync([$secondReceiverCompany->id]);
        $this->grantProjectRequirementPermissions(
            $firstReceiverUser,
            $firstReceiverCompany,
            [
                Permission::PROJECT_REQUIREMENT_LIST(),
                Permission::PROJECT_REQUIREMENT_VIEW(),
            ],
        );

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $firstReceiverCompany->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements")
            ->assertOk()
            ->assertJsonPath('pagination.result_count', 1)
            ->assertJsonPath('payload.0.id', $assignedRequirement->id)
            ->assertJsonPath('summary.total', 1);

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $firstReceiverCompany->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements?procedure_setting_id={$procedure->procedure_setting_id}")
            ->assertOk()
            ->assertJsonPath('pagination.result_count', 1)
            ->assertJsonPath('payload.0.id', $assignedRequirement->id);

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $firstReceiverCompany->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements/{$assignedRequirement->id}")
            ->assertOk()
            ->assertJsonPath('payload.id', $assignedRequirement->id);

        $this->actingAs($firstReceiverUser, 'api')
            ->withHeader('X-Tenant', $firstReceiverCompany->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements/{$otherReceiverRequirement->id}")
            ->assertNotFound();
    }

    public function test_receiver_company_cannot_create_update_or_delete_project_requirements(): void
    {
        $project = $this->createProject();
        $receiverCompany = $this->createCompany();
        $receiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $requirement = $this->createRequirement($project, [
            'requirement_code' => 'NTF-RECV-READONLY-001',
        ]);

        $this->createAcceptedShare($project, $this->company, $receiverCompany);
        $requirement->receiverCompanies()->sync([$receiverCompany->id]);
        $this->grantProjectRequirementPermissions($receiverUser, $receiverCompany);

        $this->actingAs($receiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->postJson("/api/v1/projects/{$project->id}/requirements", $this->requirementPayload('NTF-RECV-READONLY-002'))
            ->assertNotFound();

        $this->actingAs($receiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->putJson("/api/v1/projects/{$project->id}/requirements/{$requirement->id}", [
                'required_document_name' => 'Receiver should not update',
            ])
            ->assertNotFound();

        $this->actingAs($receiverUser, 'api')
            ->withHeader('X-Tenant', $receiverCompany->id)
            ->deleteJson("/api/v1/projects/{$project->id}/requirements/{$requirement->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('project_requirements', [
            'id' => $requirement->id,
            'required_document_name' => $requirement->required_document_name,
        ]);
    }

    public function test_requirement_code_is_unique_inside_each_project_only(): void
    {
        $project = $this->createProject();
        $otherProject = $this->createProject(['name' => 'Other Project']);
        $this->createRequirement($project, ['requirement_code' => 'NTF-UNIQUE-001']);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/requirements", $this->requirementPayload('NTF-UNIQUE-001'))
            ->assertUnprocessable();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$otherProject->id}/requirements", $this->requirementPayload('NTF-UNIQUE-001'))
            ->assertOk()
            ->assertJsonPath('payload.requirement_code', 'NTF-UNIQUE-001');
    }

    public function test_validation_errors_are_returned_for_missing_or_invalid_requirement_data(): void
    {
        $project = $this->createProject();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/requirements", [
                'requirement_code' => '',
                'document' => '',
                'document_type' => '',
                'repetition' => 'hourly',
                'completion_percentage' => 101,
            ])
            ->assertUnprocessable();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/requirements", $this->requirementPayload('NTF-INVALID-001', [
                'repetition' => ProjectRequirementRepetition::Once->value,
                'repeat_days' => ['saturday'],
            ]))
            ->assertUnprocessable();
    }

    public function test_project_requirement_rejects_legacy_document_type_id(): void
    {
        $project = $this->createProject();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/requirements", $this->requirementPayload('NTF-LEGACY-001', [
                'document_type_id' => (string) Str::uuid(),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['document_type_id']);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/requirements?document_type_id=".(string) Str::uuid())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['document_type_id']);
    }

    public function test_project_requirement_validates_procedure_setting_project_and_tenant_scope(): void
    {
        $project = $this->createProject();
        $otherProject = $this->createProject(['name' => 'Other Procedure Project']);
        $otherProjectProcedure = $this->createProjectProcedure($otherProject, 'Other Project Procedure');
        $otherTenantCompany = $this->createCompany(['serial_no' => 'REQ-OTHER-TENANT']);
        $otherTenantProcedure = $this->createProjectProcedure($project, 'Other Tenant Procedure', $otherTenantCompany);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/requirements", $this->requirementPayload('NTF-BAD-PROC-001', [
                'procedure_setting_id' => $otherProjectProcedure->procedure_setting_id,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['requirements.0.procedure_setting_id']);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/requirements", $this->requirementPayload('NTF-BAD-PROC-002', [
                'procedure_setting_id' => $otherTenantProcedure->procedure_setting_id,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['requirements.0.procedure_setting_id']);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/requirements", $this->requirementPayload('NTF-NULL-PROC-001', [
                'procedure_setting_id' => null,
            ]))
            ->assertOk()
            ->assertJsonPath('payload.procedure_setting_id', null)
            ->assertJsonPath('payload.procedure_setting', null);
    }

    private function schemaReady(): bool
    {
        return Schema::hasTable('project_requirements')
            && Schema::hasColumn('project_requirements', 'procedure_setting_id')
            && Schema::hasTable('project_requirement_receiver_companies')
            && Schema::hasTable('projects')
            && Schema::hasTable('project_types')
            && Schema::hasTable('procedure_settings')
            && Schema::hasTable('project_procedure_settings')
            && Schema::hasTable('work_flows')
            && Schema::hasTable('resource_shares');
    }

    private function createProject(array $overrides = []): ProjectManagement
    {
        $projectTypeId = $this->projectTypeId();

        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->withoutGlobalScopes()->forceCreate(array_merge([
            'id' => (string) Str::uuid(),
            'project_type_id' => $projectTypeId,
            'sub_project_type_id' => $projectTypeId,
            'sub_sub_project_type_id' => $projectTypeId,
            'name' => 'Project Requirement Test',
            'company_id' => $this->company->id,
            'status' => 1,
            'serial_number' => 'REQ-'.Str::upper(Str::random(6)),
        ], $overrides)));
    }

    private function projectTypeId(): int
    {
        return (int) ProjectType::query()->withoutGlobalScopes()->firstOrCreate(
            [
                'name' => 'Project Requirement Test Type',
                'company_id' => $this->company->id,
            ],
            [
                'is_created' => true,
                'is_have_schema' => false,
                'is_active' => true,
            ],
        )->id;
    }

    private function createCompany(array $overrides = []): Company
    {
        return Company::withoutEvents(fn () => Company::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Project Requirement Receiver Company'],
            'user_name' => 'project_requirement_receiver_'.Str::lower(Str::random(6)),
            'email' => 'project-requirement-receiver-'.Str::lower(Str::random(6)).'@example.test',
            'phone' => '01000000000',
            'country_id' => $this->country->id,
            'company_type_id' => (string) Str::uuid(),
            'company_field_id' => (string) Str::uuid(),
            'registration_type_id' => (string) Str::uuid(),
            'general_manager_id' => (string) Str::uuid(),
            'is_active' => 1,
            'complete_data' => 1,
            'serial_no' => 'REQ-REC-'.Str::upper(Str::random(6)),
        ], $overrides)));
    }

    private function createProjectProcedure(
        ProjectManagement $project,
        string $name = 'Document Approval',
        ?Company $company = null
    ): ProjectProcedureSetting {
        $company ??= $this->company;
        $type = ProjectProcedureSetting::PROCEDURE_TYPE;

        $workFlow = WorkFlow::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'name' => 'project_'.$project->id.'_'.Str::lower(Str::random(6)),
            'type' => $type,
        ]);

        $parent = ProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Project Procedures',
            'type' => $type,
            'execute_type' => 'sequence',
            'is_active' => true,
            'work_flow_id' => $workFlow->id,
            'parent_id' => null,
        ]);

        $procedureSetting = ProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => $name,
            'type' => $type,
            'execute_type' => 'sequence',
            'is_active' => true,
            'work_flow_id' => $workFlow->id,
            'parent_id' => $parent->id,
            'sort_order' => 1,
        ]);

        return ProjectProcedureSetting::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'procedure_setting_id' => $procedureSetting->id,
            'used_in_document_cycle' => true,
        ]);
    }

    private function createRequirement(ProjectManagement $project, array $overrides = []): ProjectRequirement
    {
        return ProjectRequirement::query()->withoutGlobalScopes()->create(array_merge([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'requirement_code' => 'NTF-'.Str::upper(Str::random(6)),
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

    private function requirementPayload(string $code, array $overrides = []): array
    {
        return array_merge([
            'requirement_code' => $code,
            'required_document_name' => 'Stamped approval document',
            'document' => 'Shop drawing - lv panel',
            'document_type' => 'Technical Submittal',
            'specialization' => 'Electrical',
            'stage' => 'Owner',
            'sending_entity' => 'Consultant',
            'review_entity' => 'Contractor',
            'repetition' => ProjectRequirementRepetition::Once->value,
        ], $overrides);
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
            Permission::PROJECT_REQUIREMENT_CREATE(),
            Permission::PROJECT_REQUIREMENT_UPDATE(),
            Permission::PROJECT_REQUIREMENT_DELETE(),
        ];

        setPermissionsTeamId($company->id);

        foreach ($permissions as $permission) {
            if (SpatiePermission::where('name', $permission)->where('guard_name', 'api')->exists()) {
                continue;
            }

            SpatiePermission::query()->forceCreate([
                'id' => (string) Str::uuid(),
                'name' => $permission,
                'guard_name' => 'api',
                'company_id' => $this->company->id,
            ]);
        }

        $user->givePermissionTo($permissions);
    }
}
