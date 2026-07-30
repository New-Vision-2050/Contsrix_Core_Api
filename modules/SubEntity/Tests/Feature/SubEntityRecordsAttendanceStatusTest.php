<?php

declare(strict_types=1);

namespace Modules\SubEntity\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Enum\CompanyUserStatus;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Program\Models\Program;
use Modules\SubEntity\Models\RegistrationForm;
use Modules\SubEntity\Models\SubEntity;
use Modules\User\Models\User;

class SubEntityRecordsAttendanceStatusTest extends BaseAttendanceReportTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_employee_records_are_enriched_with_daily_attendance_status(): void
    {
        [$subEntity, $registrationForm] = $this->createSubEntitySetup(CompanyUserRole::EMPLOYEE);
        $presentUser = $this->createCompanyUserRecord('Present Employee', $subEntity, CompanyUserRole::EMPLOYEE);
        $waitingUser = $this->createCompanyUserRecord('Waiting Employee', $subEntity, CompanyUserRole::EMPLOYEE);
        $holidayUser = $this->createCompanyUserRecord('Holiday Employee', $subEntity, CompanyUserRole::EMPLOYEE);
        $absentUser = $this->createCompanyUserRecord('Absent Employee', $subEntity, CompanyUserRole::EMPLOYEE);

        $this->createAttendance($presentUser, [
            'status' => Attendance::STATUS_ACTIVE,
            'clock_in_time' => '2026-07-30 08:00:00',
            'day_status' => 'in_location',
        ]);
        $this->createAttendance($waitingUser, [
            'status' => Attendance::STATUS_WAITING,
            'clock_in_time' => null,
            'day_status' => 'work_day',
        ]);
        $this->createAttendance($holidayUser, [
            'status' => Attendance::STATUS_HOLIDAY,
            'clock_in_time' => null,
            'is_holiday' => true,
            'day_status' => 'holiday',
        ]);

        $payload = collect($this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/sub_entities/records/list?'.http_build_query([
                'sub_entity_id' => $subEntity->id,
                'registration_form_id' => $registrationForm->id,
                'start_date' => '2026-07-30',
                'page' => 1,
                'per_page' => 10,
            ]))
            ->assertOk()
            ->json('payload'));

        $this->assertAttendanceListStatus($payload, $presentUser, 'present', 'حاضر', '2026-07-30');
        $this->assertAttendanceListStatus($payload, $waitingUser, 'required_attendance', 'مطلوب للحضور', '2026-07-30');
        $this->assertAttendanceListStatus($payload, $holidayUser, 'holiday', 'اجازه', '2026-07-30');
        $this->assertAttendanceListStatus($payload, $absentUser, 'absent', 'غائب', '2026-07-30');
    }

    public function test_employee_records_use_today_by_default_and_empty_per_page_defaults_to_ten(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-30 10:00:00'));

        [$subEntity, $registrationForm] = $this->createSubEntitySetup(CompanyUserRole::EMPLOYEE);
        foreach (range(1, 11) as $index) {
            $this->createCompanyUserRecord("Default Date Employee {$index}", $subEntity, CompanyUserRole::EMPLOYEE);
        }

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/sub_entities/records/list?'.http_build_query([
                'sub_entity_id' => $subEntity->id,
                'registration_form_id' => $registrationForm->id,
                'page' => 1,
            ]).'&per_page');

        $response->assertOk()
            ->assertJsonPath('payload.0.attendance.work_date', '2026-07-30');

        $this->assertCount(10, $response->json('payload'));
    }

    public function test_client_and_broker_records_do_not_include_attendance_object(): void
    {
        foreach ([CompanyUserRole::CLIENT, CompanyUserRole::BROKER] as $role) {
            [$subEntity, $registrationForm] = $this->createSubEntitySetup($role);
            $this->createCompanyUserRecord($role->name.' Record', $subEntity, $role);

            $this->actingAs($this->actor, 'api')
                ->withHeader('X-Tenant', $this->company->id)
                ->getJson('/api/v1/sub_entities/records/list?'.http_build_query([
                    'sub_entity_id' => $subEntity->id,
                    'registration_form_id' => $registrationForm->id,
                    'start_date' => '2026-07-30',
                    'page' => 1,
                    'per_page' => 10,
                ]))
                ->assertOk()
                ->assertJsonMissingPath('payload.0.attendance');
        }
    }

    /**
     * @return array{0: SubEntity, 1: RegistrationForm}
     */
    private function createSubEntitySetup(CompanyUserRole $role): array
    {
        $registrationForm = RegistrationForm::query()->forceCreate([
            'id' => (string) Str::uuid(),
            'name' => ['en' => $role->name.' Form'],
            'slug' => Str::slug($role->name.' Form '.Str::random(6)),
            'company_user_role_map' => $role->value,
            'is_active' => true,
        ]);

        $program = Program::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => $role->name.' Program '.Str::random(6)],
            'is_active' => true,
        ]);

        $subEntity = SubEntity::query()->create([
            'id' => (string) Str::uuid(),
            'super_entity' => 'users',
            'origin_super_entity' => 'users',
            'name' => $role->name.' Records '.Str::random(6),
            'icon' => 'PersonIcon',
            'main_program_id' => $program->id,
            'is_active' => true,
            'is_registrable' => true,
            'default_attributes' => ['name', 'email', 'phone'],
            'optional_attributes' => [],
            'registration_form_id' => $registrationForm->id,
            'company_id' => $this->company->id,
        ]);

        return [$subEntity, $registrationForm];
    }

    private function createCompanyUserRecord(string $name, SubEntity $subEntity, CompanyUserRole $role): User
    {
        $globalId = (string) Str::uuid();
        $email = Str::slug($name).'-'.Str::random(6).'@example.test';

        CompanyUser::query()->create([
            'id' => (string) Str::uuid(),
            'global_id' => $globalId,
            'name' => $name,
            'email' => $email,
            'phone' => '010'.random_int(10000000, 99999999),
            'phone_code' => '20',
            'country_id' => $this->country->id,
        ]);

        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'company_id' => $this->company->id,
            'global_company_user_id' => $globalId,
        ]);

        DB::table('company_users_companies')->insert([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'global_company_user_id' => $globalId,
            'role' => (string) $role->value,
            'status' => (string) CompanyUserStatus::ACTIVE->value,
            'sub_entity_id' => $subEntity->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAttendance(User $user, array $overrides = []): Attendance
    {
        return Attendance::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'clock_in_time' => null,
            'clock_out_time' => null,
            'start_time' => '2026-07-30 08:00:00',
            'business_date' => '2026-07-30',
            'total_work_hours' => 0,
            'total_break_hours' => 0,
            'overtime_hours' => 0,
            'late_minutes' => 0,
            'is_late' => false,
            'is_absent' => false,
            'is_holiday' => false,
            'status' => Attendance::STATUS_WAITING,
            'day_status' => 'work_day',
        ], $overrides));
    }

    private function assertAttendanceListStatus(
        Collection $payload,
        User $user,
        string $code,
        string $label,
        string $workDate
    ): void {
        $row = $payload->firstWhere('user_id', (string) $user->id);

        $this->assertNotNull($row, 'Expected employee row was not returned.');
        $this->assertSame($code, $row['attendance']['code']);
        $this->assertSame($label, $row['attendance']['label']);
        $this->assertSame($workDate, $row['attendance']['work_date']);
    }
}
