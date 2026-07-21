<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;

class ProjectProcedureSettingStepCrudTest extends BaseAttendanceReportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->schemaReady()) {
            $this->markTestSkipped('Project-scoped procedure setting steps schema is not migrated.');
        }
    }

    public function test_project_user_can_list_steps_for_project_procedure_setting(): void
    {
        $project = $this->createProject();
        $context = $this->createProcedureSettingContext($project);
        $procedureSetting = $this->createProcedureSetting($context);
        $second = $this->createStep($procedureSetting, ['name' => 'Second Step', 'step_order' => 2]);
        $first = $this->createStep($procedureSetting, ['name' => 'First Step', 'step_order' => 1]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/procedure-settings/{$procedureSetting->id}/steps")
            ->assertOk()
            ->assertJsonPath('payload.0.id', $first->id)
            ->assertJsonPath('payload.1.id', $second->id);
    }

    public function test_project_user_can_create_step_for_project_procedure_setting(): void
    {
        $project = $this->createProject();
        $context = $this->createProcedureSettingContext($project);
        $procedureSetting = $this->createProcedureSetting($context);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/procedure-settings/{$procedureSetting->id}/steps", [
                'name' => 'Created Project Step',
                'forms' => 'approve',
                'is_approve' => true,
            ])
            ->assertOk()
            ->assertJsonPath('payload.procedure_setting_id', $procedureSetting->id)
            ->assertJsonPath('payload.name', 'Created Project Step');

        $this->assertDatabaseHas('procedure_setting_steps', [
            'id' => $response->json('payload.id'),
            'procedure_setting_id' => $procedureSetting->id,
            'name' => 'Created Project Step',
        ]);
    }

    public function test_project_user_can_show_step_for_project_procedure_setting(): void
    {
        $project = $this->createProject();
        $context = $this->createProcedureSettingContext($project);
        $procedureSetting = $this->createProcedureSetting($context);
        $step = $this->createStep($procedureSetting, ['name' => 'Shown Project Step']);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/procedure-settings/{$procedureSetting->id}/steps/{$step->id}")
            ->assertOk()
            ->assertJsonPath('payload.id', $step->id)
            ->assertJsonPath('payload.procedure_setting_id', $procedureSetting->id)
            ->assertJsonPath('payload.name', 'Shown Project Step');
    }

    public function test_project_user_can_update_step_for_project_procedure_setting(): void
    {
        $project = $this->createProject();
        $context = $this->createProcedureSettingContext($project);
        $procedureSetting = $this->createProcedureSetting($context);
        $step = $this->createStep($procedureSetting, ['name' => 'Before Update']);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson("/api/v1/projects/{$project->id}/procedure-settings/{$procedureSetting->id}/steps/{$step->id}", [
                'name' => 'After Update',
                'forms' => 'accept',
                'is_accept' => true,
                'step_order' => 7,
            ])
            ->assertOk()
            ->assertJsonPath('payload.id', $step->id)
            ->assertJsonPath('payload.name', 'After Update');

        $this->assertDatabaseHas('procedure_setting_steps', [
            'id' => $step->id,
            'procedure_setting_id' => $procedureSetting->id,
            'name' => 'After Update',
            'step_order' => 7,
        ]);
    }

    public function test_project_user_can_delete_step_for_project_procedure_setting(): void
    {
        $project = $this->createProject();
        $context = $this->createProcedureSettingContext($project);
        $procedureSetting = $this->createProcedureSetting($context);
        $step = $this->createStep($procedureSetting);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->deleteJson("/api/v1/projects/{$project->id}/procedure-settings/{$procedureSetting->id}/steps/{$step->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('procedure_setting_steps', [
            'id' => $step->id,
        ]);
    }

    public function test_project_steps_api_rejects_procedure_setting_from_another_project(): void
    {
        $project = $this->createProject();
        $otherProject = $this->createProject();
        $otherContext = $this->createProcedureSettingContext($otherProject);
        $otherProcedureSetting = $this->createProcedureSetting($otherContext);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/procedure-settings/{$otherProcedureSetting->id}/steps")
            ->assertNotFound();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/procedure-settings/{$otherProcedureSetting->id}/steps", [
                'name' => 'Should Not Create',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('procedure_setting_steps', [
            'procedure_setting_id' => $otherProcedureSetting->id,
            'name' => 'Should Not Create',
        ]);
    }

    public function test_project_steps_api_rejects_show_update_and_delete_for_step_from_another_procedure_setting(): void
    {
        $project = $this->createProject();
        $context = $this->createProcedureSettingContext($project);
        $procedureSetting = $this->createProcedureSetting($context);
        $otherProcedureSetting = $this->createProcedureSetting($context, [
            'name' => 'Sibling Procedure Setting',
        ]);
        $otherStep = $this->createStep($otherProcedureSetting, ['name' => 'Other Procedure Step']);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/procedure-settings/{$procedureSetting->id}/steps/{$otherStep->id}")
            ->assertNotFound();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson("/api/v1/projects/{$project->id}/procedure-settings/{$procedureSetting->id}/steps/{$otherStep->id}", [
                'name' => 'Should Not Update',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('procedure_setting_steps', [
            'id' => $otherStep->id,
            'procedure_setting_id' => $otherProcedureSetting->id,
            'name' => 'Other Procedure Step',
        ]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->deleteJson("/api/v1/projects/{$project->id}/procedure-settings/{$procedureSetting->id}/steps/{$otherStep->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('procedure_setting_steps', [
            'id' => $otherStep->id,
        ]);
    }

    public function test_existing_shared_procedure_settings_api_still_lists_global_settings(): void
    {
        $context = $this->createProcedureSettingContext();
        $procedureSetting = $this->createProcedureSetting($context, [
            'name' => 'Shared Procedure Setting',
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson(
                '/api/v1/procedure-settings'
                ."?type={$context['work_flow']->type}"
                ."&work_flow_id={$context['work_flow']->id}"
                ."&parent_id={$context['parent']->id}"
            )
            ->assertOk();

        $ids = collect($response->json('payload.0.procedure-settings'))->pluck('id')->all();

        $this->assertContains($procedureSetting->id, $ids);
    }

    public function test_existing_shared_internal_procedures_api_still_lists_internal_procedures(): void
    {
        $context = $this->createProcedureSettingContext();
        $internalProcedure = $this->createProcedureSetting($context, [
            'name' => 'Shared Internal Procedure',
            'form' => InternalProcessForm::CreateClientRequest->value,
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/procedure-settings/internal-procedures?type='.ProcedureSettingType::ClientRequest->value)
            ->assertOk();

        $ids = collect($response->json('payload'))->pluck('id')->all();

        $this->assertContains($internalProcedure->id, $ids);
    }

    public function test_existing_shared_procedure_setting_steps_crud_still_works(): void
    {
        $context = $this->createProcedureSettingContext();
        $procedureSetting = $this->createProcedureSetting($context);

        $createResponse = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/procedure-settings/{$procedureSetting->id}/steps", [
                'name' => 'Shared Step',
                'forms' => 'approve',
                'is_approve' => true,
            ])
            ->assertOk()
            ->assertJsonPath('payload.procedure_setting_id', $procedureSetting->id)
            ->assertJsonPath('payload.name', 'Shared Step');

        $stepId = $createResponse->json('payload.id');

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/procedure-settings/{$procedureSetting->id}/steps")
            ->assertOk()
            ->assertJsonPath('payload.0.id', $stepId);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/procedure-settings/{$procedureSetting->id}/steps/{$stepId}", [
                'name' => 'Updated Shared Step',
            ])
            ->assertOk()
            ->assertJsonPath('payload.name', 'Updated Shared Step');

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->deleteJson("/api/v1/procedure-settings/{$procedureSetting->id}/steps/{$stepId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('procedure_setting_steps', [
            'id' => $stepId,
        ]);
    }

    private function schemaReady(): bool
    {
        return Schema::hasTable('projects')
            && Schema::hasTable('project_types')
            && Schema::hasTable('procedure_settings')
            && Schema::hasTable('procedure_setting_steps')
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
            'name' => 'Scoped Procedure Steps Project',
            'company_id' => $this->company->id,
            'status' => 1,
            'serial_number' => 'PSS-'.Str::upper(Str::random(6)),
        ]));
    }

    /**
     * @return array{work_flow: WorkFlow, parent: ProcedureSetting}
     */
    private function createProcedureSettingContext(?ProjectManagement $project = null): array
    {
        $workFlow = WorkFlow::query()->withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'project_id' => $project?->id,
            'name' => 'project_steps_'.Str::lower(Str::random(6)),
            'type' => ProcedureSettingType::ClientRequest->value,
        ]);

        $parent = ProcedureSetting::query()->withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'name' => 'Parent Procedure '.Str::upper(Str::random(4)),
            'type' => ProcedureSettingType::ClientRequest->value,
            'execute_type' => 'sequence',
            'work_flow_id' => $workFlow->id,
            'sort_order' => 1,
        ]);

        return [
            'work_flow' => $workFlow,
            'parent' => $parent,
        ];
    }

    /**
     * @param  array{work_flow: WorkFlow, parent: ProcedureSetting}  $context
     * @param  array<string, mixed>  $overrides
     */
    private function createProcedureSetting(array $context, array $overrides = []): ProcedureSetting
    {
        return ProcedureSetting::query()->withoutGlobalScopes()->create(array_merge([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'name' => 'Project Scoped Step Procedure '.Str::upper(Str::random(4)),
            'type' => ProcedureSettingType::ClientRequest->value,
            'execute_type' => 'sequence',
            'work_flow_id' => $context['work_flow']->id,
            'parent_id' => $context['parent']->id,
            'sort_order' => 1,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createStep(ProcedureSetting $procedureSetting, array $overrides = []): ProcedureSettingStep
    {
        return ProcedureSettingStep::query()->withoutGlobalScopes()->create(array_merge([
            'company_id' => $this->company->id,
            'procedure_setting_id' => $procedureSetting->id,
            'name' => 'Project Procedure Step '.Str::upper(Str::random(4)),
            'forms' => 'approve',
            'is_approve' => true,
            'step_order' => 1,
        ], $overrides));
    }

    private function projectTypeId(): int
    {
        return (int) ProjectType::query()->withoutGlobalScopes()->firstOrCreate(
            [
                'name' => 'Project Scoped Procedure Steps Type',
                'company_id' => $this->company->id,
            ],
            [
                'is_created' => true,
                'is_have_schema' => false,
                'is_active' => true,
            ],
        )->id;
    }
}
