<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Country\Models\Country;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\Shared\TimeUnit\Models\TimeUnit;
use Modules\User\Models\User;
use Modules\UserInfo\EmploymentContract\Models\EmploymentContract;
use Modules\UserInfo\UserSalary\Models\UserSalary;
use Tests\TestCase;

class CompanyUserProfileWidgetTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;

    private User $employee;

    private string $globalId;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->databaseReady()) {
            $this->markTestSkipped('Database seed prerequisites missing for company user profile widget tests.');
        }

        Carbon::setTestNow(Carbon::parse('2026-08-04 12:00:00', 'Asia/Riyadh'));

        $country = Country::query()->first()
            ?? Country::query()->create([
                'name' => 'Test Country',
                'phonecode' => '20',
                'status' => 1,
            ]);

        $this->company = Company::withoutEvents(fn () => Company::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Profile Widget Company'],
            'user_name' => 'profile_widget_'.Str::random(6),
            'email' => 'profile-widget-'.Str::random(6).'@example.test',
            'phone' => '01000000000',
            'country_id' => $country->id,
            'company_type_id' => (string) Str::uuid(),
            'company_field_id' => (string) Str::uuid(),
            'registration_type_id' => (string) Str::uuid(),
            'general_manager_id' => (string) Str::uuid(),
            'is_active' => 1,
            'complete_data' => 1,
            'serial_no' => 'PRF-WDG-'.Str::upper(Str::random(8)),
        ]));

        $this->company->domains()->firstOrCreate(['domain' => 'profile-widget-'.Str::random(6).'.test']);
        tenancy()->initialize($this->company);

        $this->globalId = (string) Str::uuid();
        $this->employee = User::factory()->create([
            'company_id' => $this->company->id,
            'global_company_user_id' => $this->globalId,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_widget_returns_current_month_completed_task_summary(): void
    {
        $this->seedContractAndSalary();

        foreach ([
            ['task_date' => '2026-08-01', 'status' => 'completed'],
            ['task_date' => '2026-08-10', 'status' => 'completed'],
            ['task_date' => '2026-08-15', 'status' => 'approved'],
            ['task_date' => '2026-08-20', 'status' => 'pending'],
            ['task_date' => '2026-07-31', 'status' => 'completed'],
        ] as $attributes) {
            $this->createTaskFor($this->employee, $this->company, $attributes);
        }

        $otherUser = User::factory()->create([
            'company_id' => $this->company->id,
            'global_company_user_id' => (string) Str::uuid(),
        ]);
        $this->createTaskFor($otherUser, $this->company, [
            'task_date' => '2026-08-05',
            'status' => 'completed',
        ]);

        $otherCompany = Company::withoutEvents(fn () => Company::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Other Profile Widget Company'],
            'user_name' => 'other_profile_widget_'.Str::random(6),
            'email' => 'other-profile-widget-'.Str::random(6).'@example.test',
            'phone' => '01000000001',
            'country_id' => $this->company->country_id,
            'company_type_id' => (string) Str::uuid(),
            'company_field_id' => (string) Str::uuid(),
            'registration_type_id' => (string) Str::uuid(),
            'general_manager_id' => (string) Str::uuid(),
            'is_active' => 1,
            'complete_data' => 1,
            'serial_no' => 'OPW-'.Str::upper(Str::random(8)),
        ]));
        $otherCompanyUser = User::factory()->create([
            'company_id' => $otherCompany->id,
            'global_company_user_id' => (string) Str::uuid(),
        ]);
        $this->createTaskFor($otherCompanyUser, $otherCompany, [
            'task_date' => '2026-08-06',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->employee, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/company-users/widget/user/'.(string) $this->employee->id);

        $response->assertOk()
            ->assertJsonPath('payload.tasks.period', 'current_month')
            ->assertJsonPath('payload.tasks.from_date', '2026-08-01')
            ->assertJsonPath('payload.tasks.to_date', '2026-08-31')
            ->assertJsonPath('payload.tasks.total_count', 4)
            ->assertJsonPath('payload.tasks.accepted_count', 2)
            ->assertJsonPath('payload.tasks.accepted_status', 'completed')
            ->assertJsonPath('payload.tasks.accepted_percentage', 50.0);
    }

    public function test_widget_returns_zero_task_summary_even_when_contract_data_is_missing(): void
    {
        $response = $this->actingAs($this->employee, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/company-users/widget/user/'.(string) $this->employee->id);

        $response->assertOk()
            ->assertJsonPath('payload.contract.start_date', null)
            ->assertJsonPath('payload.contract.end_date', null)
            ->assertJsonPath('payload.contract.user_salary', null)
            ->assertJsonPath('payload.tasks.period', 'current_month')
            ->assertJsonPath('payload.tasks.total_count', 0)
            ->assertJsonPath('payload.tasks.accepted_count', 0)
            ->assertJsonPath('payload.tasks.accepted_status', 'completed')
            ->assertJsonPath('payload.tasks.accepted_percentage', 0.0);
    }

    private function seedContractAndSalary(): void
    {
        $monthUnit = TimeUnit::query()->firstOrCreate(
            ['code' => 'month'],
            ['id' => (string) Str::uuid()],
        );

        EmploymentContract::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'global_id' => $this->globalId,
            'contract_number' => 'C-001',
            'start_date' => '2026-08-01',
            'commencement_date' => '2026-08-01',
            'contract_duration' => '12',
            'notice_period' => 1,
            'probation_period' => 90,
            'nature_work_id' => null,
            'type_working_hour_id' => null,
            'working_hours' => 8,
            'annual_leave' => 21,
            'country_id' => $this->company->country_id,
            'right_terminate_id' => null,
            'contract_duration_unit' => $monthUnit->id,
        ]);

        UserSalary::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'global_id' => $this->globalId,
            'salary' => 8500,
            'description' => 'Profile widget test salary',
        ]);
    }

    private function createTaskFor(User $user, Company $company, array $attributes): void
    {
        EmployeeTaskRequest::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'user_id' => $user->id,
            'serial_number' => 'PRF-TASK-'.Str::upper(Str::random(10)),
            'title' => 'Profile widget task',
            'duration_hours' => 2,
            'task_latitude' => 24.7136,
            'task_longitude' => 46.6753,
        ], $attributes));
    }

    private function databaseReady(): bool
    {
        try {
            return Schema::hasTable('countries')
                && Schema::hasTable('companies')
                && Schema::hasTable('users')
                && Schema::hasTable('employee_task_requests')
                && Schema::hasTable('employment_contracts')
                && Schema::hasTable('user_salaries')
                && Schema::hasTable('time_units');
        } catch (\Throwable) {
            return false;
        }
    }
}
