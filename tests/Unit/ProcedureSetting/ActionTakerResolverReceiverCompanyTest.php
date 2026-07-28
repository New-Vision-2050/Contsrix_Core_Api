<?php

declare(strict_types=1);

namespace Tests\Unit\ProcedureSetting;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Country\Models\Country;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\ProcedureSetting\Enums\ActionTakerType;
use Modules\ProcedureSetting\Events\WorkflowStepActivated;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\ProcedureSetting\Services\ActionTakerResolver;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\Process\Services\ProcessWorkflowService;
use Modules\User\Models\User;
use Tests\TestCase;

class ActionTakerResolverReceiverCompanyTest extends TestCase
{
    public function test_resolver_returns_all_users_for_selected_receiver_companies(): void
    {
        $firstReceiverCompany = $this->createCompany();
        $secondReceiverCompany = $this->createCompany();
        $unselectedCompany = $this->createCompany();

        $firstReceiverUser = User::factory()->create(['company_id' => $firstReceiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $firstReceiverCompany->id]);
        $thirdReceiverUser = User::factory()->create(['company_id' => $secondReceiverCompany->id]);
        $unselectedUser = User::factory()->create(['company_id' => $unselectedCompany->id]);

        $step = new ProcedureSettingStep([
            'action_taker_type' => ActionTakerType::ReceiverCompany,
            'receiver_company_ids' => [
                $firstReceiverCompany->id,
                $secondReceiverCompany->id,
            ],
        ]);

        $resolved = (new ActionTakerResolver())->resolveUsersForStep($step);

        $this->assertEqualsCanonicalizing([
            (string) $firstReceiverUser->id,
            (string) $secondReceiverUser->id,
            (string) $thirdReceiverUser->id,
        ], $resolved);
        $this->assertNotContains((string) $unselectedUser->id, $resolved);
    }

    public function test_receiver_company_users_are_authorized_in_process_snapshot(): void
    {
        Event::fake([WorkflowStepActivated::class]);

        $ownerCompany = $this->createCompany();
        tenancy()->initialize($ownerCompany);

        $receiverCompany = $this->createCompany();
        $firstReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $secondReceiverUser = User::factory()->create(['company_id' => $receiverCompany->id]);
        $ownerUser = User::factory()->create(['company_id' => $ownerCompany->id]);

        $setting = ProcedureSetting::query()->create([
            'name' => 'Receiver Company Workflow',
            'type' => 'employee_task',
            'company_id' => $ownerCompany->id,
            'execute_type' => 'sequence',
            'sort_order' => 1,
        ]);

        ProcedureSettingStep::query()->create([
            'procedure_setting_id' => $setting->id,
            'company_id' => $ownerCompany->id,
            'name' => 'Receiver Company Approval',
            'step_order' => 1,
            'action_taker_type' => ActionTakerType::ReceiverCompany,
            'receiver_company_ids' => [$receiverCompany->id],
            'is_approve' => true,
        ]);

        $task = EmployeeTaskRequest::query()->create([
            'company_id' => $ownerCompany->id,
            'user_id' => $ownerUser->id,
            'serial_number' => 'RCV-'.Str::upper(Str::random(8)),
            'title' => 'Receiver company process test',
            'duration_hours' => 1,
            'task_date' => now()->toDateString(),
            'task_latitude' => 30.0000000,
            'task_longitude' => 31.0000000,
            'status' => 'pending',
        ]);

        $service = new ProcessWorkflowService(new ActionTakerResolver());
        $process = $service->createProcessesFromSettings(
            'employee_task',
            (string) $task->id,
            new EloquentCollection([$setting]),
            (string) $ownerUser->id,
        );

        $this->assertNotNull($process);

        $processStep = $process->steps()->firstOrFail();
        $this->assertEqualsCanonicalizing([
            (string) $firstReceiverUser->id,
            (string) $secondReceiverUser->id,
        ], $processStep->authorized_user_ids);

        $this->actingAs($secondReceiverUser, 'api');
        $approvedStep = $service->approveStep((string) $processStep->id);

        $this->assertSame(ProcessStepStatus::Approved->value, $approvedStep->status->value);
        $this->assertSame((string) $secondReceiverUser->id, (string) $approvedStep->action_by);
    }

    private function createCompany(): Company
    {
        $countryId = DB::table('countries')->insertGetId([
            'name' => 'Receiver Company Test Country',
            'status' => 1,
        ]);

        $country = Country::query()->findOrFail($countryId);

        return Company::withoutEvents(fn () => Company::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Receiver Company Test'],
            'user_name' => 'receiver_company_test_'.Str::lower(Str::random(6)),
            'email' => 'receiver-company-test-'.Str::lower(Str::random(6)).'@example.test',
            'phone' => '01000000000',
            'country_id' => $country->id,
            'company_type_id' => (string) Str::uuid(),
            'company_field_id' => (string) Str::uuid(),
            'registration_type_id' => (string) Str::uuid(),
            'general_manager_id' => (string) Str::uuid(),
            'is_active' => 1,
            'complete_data' => 1,
            'serial_no' => 'RCV-'.Str::upper(Str::random(6)),
        ]));
    }
}
