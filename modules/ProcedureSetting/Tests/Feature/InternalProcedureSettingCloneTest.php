<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\ProcedureSetting\Models\ProcedureSettingStepActionTaker;
use Modules\ProcedureSetting\Models\ProcedureSettingStepConcernedManagementHierarchy;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;

class InternalProcedureSettingCloneTest extends BaseAttendanceReportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->schemaReady()) {
            $this->markTestSkipped('Procedure setting clone schema is not migrated.');
        }
    }

    public function test_internal_procedure_create_without_source_still_works_normally(): void
    {
        $parent = $this->createInternalProcedureParent();

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/procedure-settings/internal-procedures', [
                'name' => 'Create Client Request',
                'type' => ProcedureSettingType::ClientRequest->value,
                'form' => InternalProcessForm::CreateClientRequest->value,
                'execute_type' => 'sequence',
            ])
            ->assertOk()
            ->assertJsonPath('payload.name', 'Create Client Request')
            ->assertJsonPath('payload.type', ProcedureSettingType::ClientRequest->value)
            ->assertJsonPath('payload.form.key', InternalProcessForm::CreateClientRequest->value);

        $procedureSettingId = $response->json('payload.id');

        $this->assertDatabaseHas('procedure_settings', [
            'id' => $procedureSettingId,
            'parent_id' => $parent->id,
            'company_id' => $this->company->id,
            'form' => InternalProcessForm::CreateClientRequest->value,
            'name' => 'Create Client Request',
        ]);

        $this->assertSame(
            0,
            ProcedureSettingStep::query()
                ->withoutGlobalScopes()
                ->where('procedure_setting_id', $procedureSettingId)
                ->count(),
        );
    }

    public function test_internal_procedure_create_can_duplicate_source_procedure_steps_and_owned_rows(): void
    {
        $this->createInternalProcedureParent();

        $sourceConditions = [[
            'key' => InternalProcessCondition::MaxAttachments->value,
            'is_active' => true,
            'sort_order' => 5,
            'settings' => ['max' => 7],
        ]];

        $sourceId = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/procedure-settings/internal-procedures', [
                'name' => 'Source Attachments Procedure',
                'type' => ProcedureSettingType::ClientRequest->value,
                'form' => InternalProcessForm::AttachAttachments->value,
                'execute_type' => 'parallel',
                'percentage' => 62.5,
                'deadline_days' => 4,
                'deadline_hours' => 9,
                'sort_order' => 13,
                'is_active' => false,
                'conditions' => $sourceConditions,
            ])
            ->assertOk()
            ->json('payload.id');

        $sourceProcedure = ProcedureSetting::query()
            ->withoutGlobalScopes()
            ->findOrFail($sourceId);

        $sourceStep = ProcedureSettingStep::query()->withoutGlobalScopes()->create([
            'procedure_setting_id' => $sourceId,
            'company_id' => $this->company->id,
            'name' => 'Source review',
            'branch_id' => $this->branch->id,
            'management_id' => $this->management->id,
            'forms' => 'approve',
            'is_accept' => true,
            'is_approve' => true,
            'is_view_only' => true,
            'is_return_with_notes' => true,
            'requires_approval_within_period' => true,
            'approval_within_days' => 2,
            'approval_within_hours' => 6,
            'skipping_period' => 1.5,
            'notify_by_email' => true,
            'notify_by_whatsapp' => true,
            'notify_by_sms' => true,
            'notify_by_push' => true,
            'notify_by_voice' => true,
            'escalation_management_hierarchy_id' => $this->management->id,
            'step_order' => 4,
            'action_taker_type' => 'specific_user',
            'action_taker_management_hierarchy_type' => 'branch_manager',
            'action_taker_alternative_management_hierarchy_type' => ['management_manager'],
            'action_taker_specific_procedure_type' => ['branch', 'management'],
            'action_taker_specific_procedure_id' => [(string) $this->branch->id, (string) $this->management->id],
            'action_taker_management_hierarchies' => [[
                'action_taker_management_hierarchy_type' => 'branch_manager',
                'is_Deputy_Director' => false,
            ]],
        ]);

        ProcedureSettingStepActionTaker::query()->create([
            'procedure_setting_step_id' => $sourceStep->id,
            'user_id' => $this->actor->id,
            'company_id' => $this->company->id,
        ]);

        ProcedureSettingStepConcernedManagementHierarchy::query()->create([
            'procedure_setting_step_id' => $sourceStep->id,
            'management_hierarchy_id' => $this->management->id,
            'company_id' => $this->company->id,
        ]);

        $targetId = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/procedure-settings/internal-procedures', [
                'name' => 'Cloned Client Procedure',
                'type' => ProcedureSettingType::ClientRequest->value,
                'form' => InternalProcessForm::CreateClientRequest->value,
                'source_procedure_setting_id' => $sourceId,
            ])
            ->assertOk()
            ->assertJsonPath('payload.name', 'Cloned Client Procedure')
            ->assertJsonPath('payload.form.key', InternalProcessForm::CreateClientRequest->value)
            ->json('payload.id');

        $targetProcedure = ProcedureSetting::query()
            ->withoutGlobalScopes()
            ->findOrFail($targetId);

        $this->assertNotSame($sourceProcedure->id, $targetProcedure->id);
        $this->assertSame('Cloned Client Procedure', $targetProcedure->name);
        $this->assertSame($sourceProcedure->execute_type, $targetProcedure->execute_type);
        $this->assertSame((float) $sourceProcedure->percentage, (float) $targetProcedure->percentage);
        $this->assertSame($sourceProcedure->deadline_days, $targetProcedure->deadline_days);
        $this->assertSame($sourceProcedure->deadline_hours, $targetProcedure->deadline_hours);
        $this->assertSame($sourceProcedure->sort_order, $targetProcedure->sort_order);
        $this->assertSame((int) $sourceProcedure->is_active, (int) $targetProcedure->is_active);
        $this->assertSame($sourceProcedure->conditions, $targetProcedure->conditions);
        $this->assertNotSame($sourceProcedure->work_flow_id, $targetProcedure->work_flow_id);

        $this->assertDatabaseHas('management_hierarchy_work_flow', [
            'work_flow_id' => $targetProcedure->work_flow_id,
            'management_hierarchy_id' => $this->branch->id,
        ]);

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
        $this->assertSame($targetId, $targetStep->procedure_setting_id);
        $this->assertSame('Source review', $targetStep->name);
        $this->assertSame(4, $targetStep->step_order);
        $this->assertTrue($targetStep->is_accept);
        $this->assertTrue($targetStep->is_approve);
        $this->assertTrue($targetStep->is_view_only);
        $this->assertTrue($targetStep->is_return_with_notes);
        $this->assertTrue($targetStep->requires_approval_within_period);
        $this->assertSame(2, $targetStep->approval_within_days);
        $this->assertSame(6, $targetStep->approval_within_hours);
        $this->assertSame(1.5, (float) $targetStep->skipping_period);
        $this->assertTrue($targetStep->notify_by_email);
        $this->assertTrue($targetStep->notify_by_whatsapp);
        $this->assertTrue($targetStep->notify_by_sms);
        $this->assertTrue($targetStep->notify_by_push);
        $this->assertTrue($targetStep->notify_by_voice);
        $this->assertSame('specific_user', $targetStep->action_taker_type?->value);
        $this->assertSame('branch_manager', $targetStep->action_taker_management_hierarchy_type?->value);
        $this->assertSame(['management_manager'], $targetStep->action_taker_alternative_management_hierarchy_type);
        $this->assertSame(['branch', 'management'], $targetStep->action_taker_specific_procedure_type);
        $this->assertSame(
            [(string) $this->branch->id, (string) $this->management->id],
            array_map('strval', $targetStep->action_taker_specific_procedure_id),
        );
        $this->assertSame(
            $sourceStep->action_taker_management_hierarchies,
            $targetStep->action_taker_management_hierarchies,
        );

        $this->assertDatabaseHas('procedure_setting_step_action_takers', [
            'procedure_setting_step_id' => $targetStep->id,
            'user_id' => $this->actor->id,
        ]);
        $this->assertDatabaseHas('procedure_setting_step_concerned_management_hierarchies', [
            'procedure_setting_step_id' => $targetStep->id,
            'management_hierarchy_id' => $this->management->id,
        ]);

        $this->assertSame(
            'Source Attachments Procedure',
            ProcedureSetting::query()->withoutGlobalScopes()->findOrFail($sourceId)->name,
        );
        $this->assertSame(
            'Source review',
            ProcedureSettingStep::query()->withoutGlobalScopes()->findOrFail($sourceStep->id)->name,
        );
    }

    public function test_internal_procedure_create_rejects_nonexistent_source_procedure_setting(): void
    {
        $this->createInternalProcedureParent();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/procedure-settings/internal-procedures', [
                'name' => 'Invalid Source Clone',
                'type' => ProcedureSettingType::ClientRequest->value,
                'form' => InternalProcessForm::CreateClientRequest->value,
                'source_procedure_setting_id' => (string) Str::uuid(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['source_procedure_setting_id']);
    }

    private function createInternalProcedureParent(
        string $type = ProcedureSettingType::ClientRequest->value
    ): ProcedureSetting {
        $workFlow = WorkFlow::query()->withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'name' => 'internal_parent_'.Str::lower(Str::random(6)),
            'type' => $type,
        ]);

        $workFlow->managementHierarchies()->syncWithoutDetaching([$this->branch->id]);

        return ProcedureSetting::query()->withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'name' => 'Internal Parent '.Str::upper(Str::random(4)),
            'type' => $type,
            'execute_type' => 'sequence',
            'work_flow_id' => $workFlow->id,
            'sort_order' => 1,
        ]);
    }

    private function schemaReady(): bool
    {
        return Schema::hasTable('procedure_settings')
            && Schema::hasTable('procedure_setting_steps')
            && Schema::hasTable('procedure_setting_step_action_takers')
            && Schema::hasTable('procedure_setting_step_concerned_management_hierarchies')
            && Schema::hasTable('work_flows')
            && Schema::hasTable('management_hierarchy_work_flow');
    }
}
