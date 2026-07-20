<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectType\Models\ProjectType;

class ProjectScopedProcedureSettingStoreTest extends BaseAttendanceReportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->schemaReady()) {
            $this->markTestSkipped('Project-scoped procedure setting schema is not migrated.');
        }
    }

    public function test_project_scoped_route_stores_setting_under_route_project_work_flow(): void
    {
        $project = $this->createProject();
        $context = $this->createProcedureSettingContext($project);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/procedure-settings", [
                'name' => 'Project Approval Procedure',
                'type' => ProcedureSettingType::ClientRequest->value,
                'execute_type' => 'sequence',
                'percentage' => 50,
                'work_flow_id' => $context['work_flow']->id,
                'parent_id' => $context['parent']->id,
            ])
            ->assertOk();

        $procedureSettingId = $response->json('payload.id');

        $this->assertNotEmpty($procedureSettingId);
        $this->assertDatabaseHas('work_flows', [
            'id' => $context['work_flow']->id,
            'project_id' => $project->id,
        ]);
        $this->assertDatabaseHas('procedure_settings', [
            'id' => $procedureSettingId,
            'name' => 'Project Approval Procedure',
            'work_flow_id' => $context['work_flow']->id,
            'parent_id' => $context['parent']->id,
        ]);
    }

    public function test_project_scoped_route_accepts_project_procedure_type(): void
    {
        $project = $this->createProject();
        $type = ProcedureSettingType::ProjectProcedure->value;
        $context = $this->createProcedureSettingContext($project, $type);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/procedure-settings", [
                'name' => 'Project Procedure Approval',
                'type' => $type,
                'execute_type' => 'sequence',
                'work_flow_id' => $context['work_flow']->id,
                'parent_id' => $context['parent']->id,
            ])
            ->assertOk();

        $procedureSettingId = $response->json('payload.id');

        $this->assertDatabaseHas('work_flows', [
            'id' => $context['work_flow']->id,
            'project_id' => $project->id,
            'type' => $type,
        ]);
        $this->assertDatabaseHas('procedure_settings', [
            'id' => $procedureSettingId,
            'type' => $type,
            'work_flow_id' => $context['work_flow']->id,
            'parent_id' => $context['parent']->id,
        ]);
    }

    public function test_project_scoped_route_uses_route_project_work_flow_instead_of_body_project_id(): void
    {
        $routeProject = $this->createProject();
        $bodyProject = $this->createProject();
        $context = $this->createProcedureSettingContext($routeProject);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$routeProject->id}/procedure-settings", [
                'name' => 'Route Project Procedure',
                'type' => ProcedureSettingType::ClientRequest->value,
                'execute_type' => 'parallel',
                'project_id' => $bodyProject->id,
                'work_flow_id' => $context['work_flow']->id,
                'parent_id' => $context['parent']->id,
            ])
            ->assertOk();

        $procedureSettingId = $response->json('payload.id');

        $this->assertDatabaseHas('procedure_settings', [
            'id' => $procedureSettingId,
            'work_flow_id' => $context['work_flow']->id,
        ]);
        $this->assertDatabaseHas('work_flows', [
            'id' => $context['work_flow']->id,
            'project_id' => $routeProject->id,
        ]);
        $this->assertDatabaseMissing('work_flows', [
            'id' => $context['work_flow']->id,
            'project_id' => $bodyProject->id,
        ]);
    }

    public function test_existing_procedure_setting_store_route_keeps_global_work_flow_behavior(): void
    {
        $project = $this->createProject();
        $context = $this->createProcedureSettingContext();

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/procedure-settings', [
                'name' => 'Global Approval Procedure',
                'type' => ProcedureSettingType::ClientRequest->value,
                'execute_type' => 'sequence',
                'project_id' => $project->id,
                'work_flow_id' => $context['work_flow']->id,
                'parent_id' => $context['parent']->id,
            ])
            ->assertOk();

        $procedureSettingId = $response->json('payload.id');

        $this->assertDatabaseHas('work_flows', [
            'id' => $context['work_flow']->id,
            'project_id' => null,
        ]);
        $this->assertDatabaseHas('procedure_settings', [
            'id' => $procedureSettingId,
            'name' => 'Global Approval Procedure',
            'work_flow_id' => $context['work_flow']->id,
        ]);
    }

    public function test_project_scoped_index_returns_only_project_procedure_settings(): void
    {
        $project = $this->createProject();
        $otherProject = $this->createProject();
        $context = $this->createProcedureSettingContext($project);
        $otherContext = $this->createProcedureSettingContext($otherProject);
        $globalContext = $this->createProcedureSettingContext();

        $visible = $this->createProcedureSetting($context, [
            'name' => 'Visible Procedure',
        ]);
        $hiddenOtherProject = $this->createProcedureSetting($otherContext, [
            'name' => 'Other Project Procedure',
        ]);
        $hiddenGlobal = $this->createProcedureSetting($globalContext, [
            'name' => 'Global Procedure',
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson(
                "/api/v1/projects/{$project->id}/procedure-settings"
                ."?type={$context['work_flow']->type}"
                ."&work_flow_id={$context['work_flow']->id}"
                ."&parent_id={$context['parent']->id}"
            )
            ->assertOk();

        $ids = collect($response->json('payload.0.procedure-settings'))->pluck('id')->all();

        $this->assertContains($visible->id, $ids);
        $this->assertNotContains($hiddenOtherProject->id, $ids);
        $this->assertNotContains($hiddenGlobal->id, $ids);
    }

    public function test_project_scoped_show_returns_only_items_for_route_project(): void
    {
        $project = $this->createProject();
        $otherProject = $this->createProject();
        $context = $this->createProcedureSettingContext($project);
        $otherContext = $this->createProcedureSettingContext($otherProject);

        $visible = $this->createProcedureSetting($context);
        $hidden = $this->createProcedureSetting($otherContext);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/procedure-settings/{$visible->id}")
            ->assertOk()
            ->assertJsonPath('payload.id', $visible->id);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/procedure-settings/{$hidden->id}")
            ->assertNotFound();
    }

    public function test_project_scoped_update_keeps_route_project_work_flow_and_reuses_update_logic(): void
    {
        $project = $this->createProject();
        $bodyProject = $this->createProject();
        $context = $this->createProcedureSettingContext($project);
        $procedureSetting = $this->createProcedureSetting($context, [
            'name' => 'Before Update',
        ]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson("/api/v1/projects/{$project->id}/procedure-settings/{$procedureSetting->id}", [
                'name' => 'After Update',
                'type' => ProcedureSettingType::ClientRequest->value,
                'execute_type' => 'parallel',
                'percentage' => 75,
                'project_id' => $bodyProject->id,
                'work_flow_id' => $context['work_flow']->id,
                'parent_id' => $context['parent']->id,
            ])
            ->assertOk()
            ->assertJsonPath('payload.id', $procedureSetting->id)
            ->assertJsonPath('payload.name', 'After Update')
            ->assertJsonPath('payload.execute_type', 'parallel');

        $this->assertDatabaseHas('procedure_settings', [
            'id' => $procedureSetting->id,
            'name' => 'After Update',
            'work_flow_id' => $context['work_flow']->id,
            'execute_type' => 'parallel',
        ]);
    }

    public function test_project_scoped_update_returns_404_for_other_project_items(): void
    {
        $project = $this->createProject();
        $otherProject = $this->createProject();
        $context = $this->createProcedureSettingContext($project);
        $otherContext = $this->createProcedureSettingContext($otherProject);
        $hidden = $this->createProcedureSetting($otherContext);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson("/api/v1/projects/{$project->id}/procedure-settings/{$hidden->id}", [
                'name' => 'Should Not Update',
                'type' => ProcedureSettingType::ClientRequest->value,
                'execute_type' => 'sequence',
                'work_flow_id' => $context['work_flow']->id,
                'parent_id' => $context['parent']->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('procedure_settings', [
            'id' => $hidden->id,
            'name' => 'Should Not Update',
        ]);
    }

    public function test_project_scoped_update_returns_404_when_moving_to_other_project_work_flow(): void
    {
        $project = $this->createProject();
        $otherProject = $this->createProject();
        $context = $this->createProcedureSettingContext($project);
        $otherContext = $this->createProcedureSettingContext($otherProject);
        $procedureSetting = $this->createProcedureSetting($context);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson("/api/v1/projects/{$project->id}/procedure-settings/{$procedureSetting->id}", [
                'name' => 'Move Attempt',
                'type' => ProcedureSettingType::ClientRequest->value,
                'execute_type' => 'sequence',
                'work_flow_id' => $otherContext['work_flow']->id,
                'parent_id' => $otherContext['parent']->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('procedure_settings', [
            'id' => $procedureSetting->id,
            'work_flow_id' => $context['work_flow']->id,
        ]);
        $this->assertDatabaseMissing('procedure_settings', [
            'id' => $procedureSetting->id,
            'work_flow_id' => $otherContext['work_flow']->id,
        ]);
    }

    public function test_project_scoped_delete_deletes_only_items_for_route_project(): void
    {
        $project = $this->createProject();
        $otherProject = $this->createProject();
        $context = $this->createProcedureSettingContext($project);
        $otherContext = $this->createProcedureSettingContext($otherProject);

        $visible = $this->createProcedureSetting($context);
        $hidden = $this->createProcedureSetting($otherContext);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->deleteJson("/api/v1/projects/{$project->id}/procedure-settings/{$hidden->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('procedure_settings', [
            'id' => $hidden->id,
            'work_flow_id' => $otherContext['work_flow']->id,
        ]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->deleteJson("/api/v1/projects/{$project->id}/procedure-settings/{$visible->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('procedure_settings', [
            'id' => $visible->id,
        ]);
    }

    private function schemaReady(): bool
    {
        return Schema::hasTable('projects')
            && Schema::hasTable('project_types')
            && Schema::hasTable('procedure_settings')
            && Schema::hasTable('work_flows')
            && Schema::hasColumn('work_flows', 'project_id');
    }

    private function createProject(): ProjectManagement
    {
        $projectTypeId = $this->projectTypeId();

        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->withoutGlobalScopes()->forceCreate([
            'id' => (string) Str::uuid(),
            'project_type_id' => $projectTypeId,
            'sub_project_type_id' => $projectTypeId,
            'sub_sub_project_type_id' => $projectTypeId,
            'name' => 'Scoped Procedure Project',
            'company_id' => $this->company->id,
            'status' => 1,
            'serial_number' => 'PSP-'.Str::upper(Str::random(6)),
        ]));
    }

    /**
     * @param  array{work_flow: WorkFlow, parent: ProcedureSetting}  $context
     * @param  array<string, mixed>  $overrides
     */
    private function createProcedureSetting(
        array $context,
        array $overrides = []
    ): ProcedureSetting {
        return ProcedureSetting::query()->withoutGlobalScopes()->create(array_merge([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'name' => 'Project Scoped Procedure '.Str::upper(Str::random(4)),
            'type' => ProcedureSettingType::ClientRequest->value,
            'execute_type' => 'sequence',
            'work_flow_id' => $context['work_flow']->id,
            'parent_id' => $context['parent']->id,
            'sort_order' => 1,
        ], $overrides));
    }

    private function projectTypeId(): int
    {
        return (int) ProjectType::query()->withoutGlobalScopes()->firstOrCreate(
            [
                'name' => 'Project Scoped Procedure Type',
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
     * @return array{work_flow: WorkFlow, parent: ProcedureSetting}
     */
    private function createProcedureSettingContext(?ProjectManagement $project = null, ?string $type = null): array
    {
        $type ??= ProcedureSettingType::ClientRequest->value;

        $workFlow = WorkFlow::query()->withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'project_id' => $project?->id,
            'name' => 'project_scoped_'.Str::lower(Str::random(6)),
            'type' => $type,
        ]);

        $parent = ProcedureSetting::query()->withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'name' => 'Parent Procedure '.Str::upper(Str::random(4)),
            'type' => $type,
            'execute_type' => 'sequence',
            'work_flow_id' => $workFlow->id,
            'sort_order' => 1,
        ]);

        return [
            'work_flow' => $workFlow,
            'parent' => $parent,
        ];
    }
}
