<?php

declare(strict_types=1);

namespace Tests\Unit\Process;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Country\Models\Country;
use Modules\ProcedureSetting\Enums\ActionTakerType;
use Modules\ProcedureSetting\Events\WorkflowStepActivated;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\ProcedureSetting\Services\ActionTakerResolver;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Models\Process;
use Modules\Process\Services\ProcessWorkflowService;
use Modules\User\Models\User;
use Tests\TestCase;

class ProcessWorkflowServiceMultipleProcessesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_repeated_lifecycle_form_requests_create_separate_processes(): void
    {
        Event::fake([WorkflowStepActivated::class]);

        $countryId = DB::table('countries')->insertGetId([
            'name' => 'Test Country',
            'status' => 1,
        ]);
        $country = Country::find($countryId);
        $company = Company::factory()->create(['country_id' => $country->id]);
        tenancy()->initialize($company);

        $user = User::factory()->create();

        $parent = ProcedureSetting::create([
            'name' => 'Project Notification Task',
            'type' => 'project_notification_task',
            'company_id' => $company->id,
            'sort_order' => 1,
        ]);

        $setting = ProcedureSetting::create([
            'name' => 'Site Status Update',
            'type' => 'project_notification_task',
            'company_id' => $company->id,
            'parent_id' => $parent->id,
            'form' => 'updateProjectNotificationSiteStatus',
            'sort_order' => 1,
        ]);

        ProcedureSettingStep::create([
            'procedure_setting_id' => $setting->id,
            'company_id' => $company->id,
            'name' => 'Approve Site Status Update',
            'step_order' => 1,
            'action_taker_type' => ActionTakerType::ManagementHierarchy,
            'is_approve' => true,
        ]);

        $resolver = Mockery::mock(ActionTakerResolver::class);
        $resolver->shouldReceive('resolveUsersForStep')
            ->andReturn([(string) $user->id]);

        $service = new ProcessWorkflowService($resolver);

        $firstProcess = $service->createProcessesFromSettings(
            'project_notification_task',
            'task-id',
            collect([$setting]),
            (string) $user->id,
        );

        $secondProcess = $service->createProcessesFromSettings(
            'project_notification_task',
            'task-id',
            collect([$setting]),
            (string) $user->id,
        );

        $this->assertNotNull($firstProcess);
        $this->assertNotNull($secondProcess);
        $this->assertNotEquals($firstProcess->id, $secondProcess->id);
        $this->assertEquals(ProcessStatus::InProgress, $firstProcess->status);
        $this->assertEquals(ProcessStatus::InProgress, $secondProcess->status);
        $this->assertNotEquals($firstProcess->sort_order, $secondProcess->sort_order);
        $this->assertEquals(2, Process::count());
    }

    public function test_non_form_setting_still_skips_duplicate_sort_order(): void
    {
        Event::fake([WorkflowStepActivated::class]);

        $countryId = DB::table('countries')->insertGetId([
            'name' => 'Test Country',
            'status' => 1,
        ]);
        $country = Country::find($countryId);
        $company = Company::factory()->create(['country_id' => $country->id]);
        tenancy()->initialize($company);

        $user = User::factory()->create();

        $setting = ProcedureSetting::create([
            'name' => 'One-time Workflow',
            'type' => 'project_notification_task',
            'company_id' => $company->id,
            'sort_order' => 1,
        ]);

        ProcedureSettingStep::create([
            'procedure_setting_id' => $setting->id,
            'company_id' => $company->id,
            'name' => 'Approve',
            'step_order' => 1,
            'action_taker_type' => ActionTakerType::ManagementHierarchy,
            'is_approve' => true,
        ]);

        $resolver = Mockery::mock(ActionTakerResolver::class);
        $resolver->shouldReceive('resolveUsersForStep')
            ->andReturn([(string) $user->id]);

        $service = new ProcessWorkflowService($resolver);

        $firstProcess = $service->createProcessesFromSettings(
            'project_notification_task',
            'task-id',
            collect([$setting]),
            (string) $user->id,
        );

        $secondProcess = $service->createProcessesFromSettings(
            'project_notification_task',
            'task-id',
            collect([$setting]),
            (string) $user->id,
        );

        $this->assertNotNull($firstProcess);
        $this->assertNull($secondProcess);
        $this->assertEquals(1, Process::count());
    }
}
