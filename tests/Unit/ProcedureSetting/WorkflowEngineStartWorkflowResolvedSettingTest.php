<?php

declare(strict_types=1);

namespace Tests\Unit\ProcedureSetting;

use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Services\ActionTakerResolver;
use Modules\ProcedureSetting\Services\WorkflowEngine;
use Modules\Process\Services\ProcessWorkflowService;
use Tests\TestCase;

class WorkflowEngineStartWorkflowResolvedSettingTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_start_workflow_with_resolved_setting_passes_eloquent_collection(): void
    {
        $resolver = Mockery::mock(ActionTakerResolver::class);
        $processService = Mockery::mock(ProcessWorkflowService::class);

        $resolvedSetting = Mockery::mock(ProcedureSetting::class)->makePartial();
        $resolvedSetting->shouldReceive('load')
            ->once()
            ->andReturn($resolvedSetting);

        $processService->shouldReceive('createProcessesFromSettings')
            ->once()
            ->with(
                'employee_task',
                'task-id',
                Mockery::on(static function ($settings): bool {
                    return $settings instanceof Collection && $settings->count() === 1;
                }),
                null,
                [],
                null,
            )
            ->andReturnNull();

        $engine = new WorkflowEngine($resolver, $processService);

        $result = $engine->startWorkflow(
            processableType: 'employee_task',
            processableId: 'task-id',
            type: 'employee_task',
            formKey: 'updateProjectNotificationTask',
            companyId: 'company-id',
            branchId: null,
            createdByUserId: null,
            context: [],
            metadata: null,
            resolvedSetting: $resolvedSetting,
        );

        $this->assertTrue($result->autoApprove);
        $this->assertNull($result->activeProcess);
    }
}
