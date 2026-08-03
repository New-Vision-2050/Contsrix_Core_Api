<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\ArchiveLibrary\File\Jobs\SyncEmployeeArchiveProfileJob;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\ArchiveLibrary\File\Services\EmployeeArchiveFileService;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Enum\CompanyUserStatus;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Models\CompanyUserCompany;
use Modules\Shared\Media\Models\CustomMedia;
use Modules\User\Models\User;
use Modules\UserInfo\EmploymentContract\Models\EmploymentContract;
use Modules\UserInfo\JobOffer\Models\JobOffer;
use Modules\UserInfo\ProfessionalCertificate\Models\ProfessionalCertificate;
use Modules\UserInfo\Qualification\Models\Qualification;
use Modules\UserInfo\UserEducationalCourse\Models\UserEducationalCourse;
use Tests\TestCase;
use Throwable;

class EmployeeArchiveSyncCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->databaseReady()) {
            $this->markTestSkipped('Database seed prerequisites missing for employee archive sync tests.');
        }

        config(['pcloud.enabled' => false]);

        if (tenancy()->initialized) {
            tenancy()->end();
        }
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_sync_command_backfills_employee_profile_media_and_is_idempotent(): void
    {
        $company = $this->createCompany('employee-archive-sync');
        [$user, $companyUser] = $this->createEmployee($company, 'سليم منصور');

        $qualification = $this->createQualification($company, $user->global_company_user_id);
        $course = $this->createCourse($company, $user->global_company_user_id);
        $certificate = $this->createProfessionalCertificate($company, $user->global_company_user_id);
        $jobOffer = $this->createJobOffer($company, $user->global_company_user_id);
        $employmentContract = $this->createEmploymentContract($company, $user->global_company_user_id);

        $mediaItems = [
            $this->createSourceMedia($companyUser, 'upload_user', 'profile-photo.png'),
            $this->createSourceMedia($companyUser, 'file_identity', 'identity.pdf'),
            $this->createSourceMedia($companyUser, 'upload_biography', 'cv.pdf'),
            $this->createSourceMedia($qualification, 'upload_Qualification', 'degree.pdf'),
            $this->createSourceMedia($course, 'upload', 'course.pdf'),
            $this->createSourceMedia($certificate, 'upload', 'certificate.pdf'),
            $this->createSourceMedia($jobOffer, 'upload_offerjob', 'offer.pdf'),
            $this->createSourceMedia($employmentContract, 'upload_employment_contracts', 'contract.pdf'),
        ];

        $this->artisan('employee-archive:sync', ['--company' => $company->id])
            ->assertExitCode(0);

        $this->assertSame(
            count($mediaItems),
            File::query()
                ->withoutTenancy()
                ->where('company_id', $company->id)
                ->where('type', EmployeeArchiveFileService::TYPE_EMPLOYEE)
                ->where('employee_global_id', $user->global_company_user_id)
                ->whereNotNull('source_media_id')
                ->count()
        );

        $this->assertArchiveFileInSubsection($mediaItems[0], EmployeeArchiveFileService::SECTION_PERSONAL, EmployeeArchiveFileService::SUB_PERSONAL_PHOTO);
        $this->assertArchiveFileInSubsection($mediaItems[1], EmployeeArchiveFileService::SECTION_PERSONAL, EmployeeArchiveFileService::SUB_RESIDENCE_INFO);
        $this->assertArchiveFileInSubsection($mediaItems[2], EmployeeArchiveFileService::SECTION_ACADEMIC, EmployeeArchiveFileService::SUB_CV);
        $this->assertArchiveFileInSubsection($mediaItems[3], EmployeeArchiveFileService::SECTION_ACADEMIC, EmployeeArchiveFileService::SUB_QUALIFICATION);
        $this->assertArchiveFileInSubsection($mediaItems[4], EmployeeArchiveFileService::SECTION_ACADEMIC, EmployeeArchiveFileService::SUB_COURSES);
        $this->assertArchiveFileInSubsection($mediaItems[5], EmployeeArchiveFileService::SECTION_ACADEMIC, EmployeeArchiveFileService::SUB_PROFESSIONAL_CERTIFICATES);
        $this->assertArchiveFileInSubsection($mediaItems[6], EmployeeArchiveFileService::SECTION_EMPLOYMENT, EmployeeArchiveFileService::SUB_JOB_OFFER);
        $this->assertArchiveFileInSubsection($mediaItems[7], EmployeeArchiveFileService::SECTION_EMPLOYMENT, EmployeeArchiveFileService::SUB_EMPLOYMENT_CONTRACT);

        $archiveMediaCount = CustomMedia::query()
            ->where('model_type', File::class)
            ->where('collection_name', 'upload')
            ->count();

        $this->artisan('employee-archive:sync', ['--company' => $company->id])
            ->assertExitCode(0);

        $this->assertSame(
            count($mediaItems),
            File::query()
                ->withoutTenancy()
                ->where('company_id', $company->id)
                ->where('type', EmployeeArchiveFileService::TYPE_EMPLOYEE)
                ->where('employee_global_id', $user->global_company_user_id)
                ->whereNotNull('source_media_id')
                ->count()
        );
        $this->assertSame(
            $archiveMediaCount,
            CustomMedia::query()
                ->where('model_type', File::class)
                ->where('collection_name', 'upload')
                ->count()
        );
    }

    public function test_dry_run_does_not_create_archive_records(): void
    {
        $company = $this->createCompany('employee-archive-dry-run');
        [$user, $companyUser] = $this->createEmployee($company, 'ندى عادل');

        $this->createSourceMedia($companyUser, 'upload_user', 'profile-photo.png');

        $this->artisan('employee-archive:sync', [
            '--company' => $company->id,
            '--dry-run' => true,
        ])->assertExitCode(0);

        $this->assertSame(
            0,
            Folder::query()
                ->withoutTenancy()
                ->where('company_id', $company->id)
                ->where('type', EmployeeArchiveFileService::TYPE_EMPLOYEE)
                ->where('employee_global_id', $user->global_company_user_id)
                ->count()
        );
        $this->assertSame(
            0,
            File::query()
                ->withoutTenancy()
                ->where('company_id', $company->id)
                ->where('type', EmployeeArchiveFileService::TYPE_EMPLOYEE)
                ->where('employee_global_id', $user->global_company_user_id)
                ->count()
        );
    }

    public function test_sync_api_queues_selected_employees_or_all_company_employees(): void
    {
        Queue::fake();

        $company = $this->createCompany('employee-archive-api');
        [$firstUser, $firstCompanyUser] = $this->createEmployee($company, 'ليلى حسن');
        [, $secondCompanyUser] = $this->createEmployee($company, 'مروان فؤاد');

        $this->createSourceMedia($firstCompanyUser, 'upload_user', 'first-profile.png');
        $this->createSourceMedia($secondCompanyUser, 'upload_user', 'second-profile.png');

        $this->actingAs($firstUser, 'api')
            ->withHeader('X-Tenant', $company->id)
            ->postJson('/api/v1/files/employee-archive/sync', [
                'employee_global_ids' => [$firstUser->global_company_user_id],
            ])
            ->assertStatus(202)
            ->assertJsonPath('message', 'Employee archive sync queued')
            ->assertJsonPath('data.company_id', (string) $company->id)
            ->assertJsonPath('data.employee_global_ids.0', (string) $firstUser->global_company_user_id)
            ->assertJsonPath('data.dry_run', false);

        Queue::assertPushed(
            SyncEmployeeArchiveProfileJob::class,
            fn (SyncEmployeeArchiveProfileJob $job): bool => $job->companyId === (string) $company->id
                && $job->employeeGlobalIds === [(string) $firstUser->global_company_user_id]
                && $job->dryRun === false
        );

        $this->actingAs($firstUser, 'api')
            ->withHeader('X-Tenant', $company->id)
            ->postJson('/api/v1/files/employee-archive/sync')
            ->assertStatus(202)
            ->assertJsonPath('data.company_id', (string) $company->id)
            ->assertJsonPath('data.employee_global_ids', null);

        Queue::assertPushed(
            SyncEmployeeArchiveProfileJob::class,
            fn (SyncEmployeeArchiveProfileJob $job): bool => $job->companyId === (string) $company->id
                && $job->employeeGlobalIds === null
                && $job->dryRun === false
        );

        Queue::assertPushed(SyncEmployeeArchiveProfileJob::class, 2);
    }

    public function test_sync_reuses_existing_employee_folder_and_attaches_missing_archive_media(): void
    {
        $company = $this->createCompany('employee-archive-existing');
        [$user, $companyUser] = $this->createEmployee($company, 'آدم سامي');
        $sourceMedia = $this->createSourceMedia($companyUser, 'upload_user', 'profile-photo.png');

        $existingRoot = Folder::query()->withoutTenancy()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Old Name',
            'parent_id' => null,
            'project_id' => null,
            'access_type' => 'public',
            'status' => 1,
            'type' => EmployeeArchiveFileService::TYPE_EMPLOYEE,
            'employee_global_id' => $user->global_company_user_id,
            'company_id' => $company->id,
        ]);

        $existingFile = File::query()->withoutTenancy()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Old Photo',
            'reference_number' => (string) Str::uuid(),
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'folder_id' => null,
            'project_id' => null,
            'access_type' => 'public',
            'status' => 1,
            'company_id' => $company->id,
            'type' => EmployeeArchiveFileService::TYPE_EMPLOYEE,
            'employee_global_id' => $user->global_company_user_id,
            'employee_section' => EmployeeArchiveFileService::SECTION_PERSONAL,
            'employee_sub_section' => EmployeeArchiveFileService::SUB_PERSONAL_PHOTO,
            'source_model_type' => CompanyUser::class,
            'source_model_id' => $companyUser->id,
            'source_media_id' => $sourceMedia->id,
        ]);

        $this->artisan('employee-archive:sync', ['--company' => $company->id])
            ->assertExitCode(0);

        $this->assertSame(
            1,
            File::query()
                ->withoutTenancy()
                ->where('source_media_id', $sourceMedia->id)
                ->count()
        );

        $this->assertSame($user->name, $existingRoot->fresh()->name);
        $this->assertNotNull($existingFile->fresh()->folder_id);
        $this->assertSame(
            1,
            CustomMedia::query()
                ->where('model_type', File::class)
                ->where('model_id', $existingFile->id)
                ->where('collection_name', 'upload')
                ->count()
        );
    }

    private function assertArchiveFileInSubsection(CustomMedia $sourceMedia, string $section, string $subsection): void
    {
        $file = File::query()
            ->withoutTenancy()
            ->where('source_media_id', $sourceMedia->id)
            ->firstOrFail();

        $subsectionFolder = Folder::query()
            ->withoutTenancy()
            ->where('id', $file->folder_id)
            ->where('name', $subsection)
            ->firstOrFail();

        $sectionFolder = Folder::query()
            ->withoutTenancy()
            ->where('id', $subsectionFolder->parent_id)
            ->where('name', $section)
            ->firstOrFail();

        $this->assertNotNull($sectionFolder);
        $this->assertSame(
            1,
            CustomMedia::query()
                ->where('model_type', File::class)
                ->where('model_id', $file->id)
                ->where('collection_name', 'upload')
                ->count()
        );
    }

    private function createCompany(string $prefix): Company
    {
        return Company::withoutEvents(fn () => Company::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => Str::headline($prefix)],
            'user_name' => $prefix.'-'.Str::random(8),
            'email' => $prefix.'-'.Str::random(8).'@example.test',
            'phone' => '010'.random_int(10000000, 99999999),
            'country_id' => (string) Str::uuid(),
            'company_type_id' => (string) Str::uuid(),
            'company_field_id' => (string) Str::uuid(),
            'registration_type_id' => (string) Str::uuid(),
            'general_manager_id' => (string) Str::uuid(),
            'is_active' => 1,
            'complete_data' => 1,
            'serial_no' => 'EMP-ARCH-'.Str::upper(Str::random(8)),
        ]));
    }

    /**
     * @return array{0: User, 1: CompanyUser}
     */
    private function createEmployee(Company $company, string $name): array
    {
        $globalId = (string) Str::uuid();

        $companyUser = CompanyUser::query()->forceCreate([
            'id' => (string) Str::uuid(),
            'global_id' => $globalId,
            'name' => $name,
            'email' => Str::random(8).'@example.test',
            'phone' => '011'.random_int(10000000, 99999999),
            'phone_code' => 'EG',
            'country_id' => 20,
        ]);

        $user = User::factory()->create([
            'name' => $name,
            'company_id' => $company->id,
            'global_company_user_id' => $globalId,
        ]);

        CompanyUserCompany::query()->withoutTenancy()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'global_company_user_id' => $globalId,
            'role' => CompanyUserRole::EMPLOYEE->value,
            'status' => CompanyUserStatus::ACTIVE->value,
        ]);

        return [$user, $companyUser];
    }

    private function createSourceMedia(Model $model, string $collection, string $fileName): CustomMedia
    {
        return CustomMedia::withoutEvents(fn () => CustomMedia::query()->forceCreate([
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'uuid' => (string) Str::uuid(),
            'collection_name' => $collection,
            'name' => pathinfo($fileName, PATHINFO_FILENAME),
            'file_name' => $fileName,
            'mime_type' => str_ends_with($fileName, '.png') ? 'image/png' : 'application/pdf',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => 1024,
            'manipulations' => [],
            'custom_properties' => [
                'file_path' => 'employee-profile/'.$collection,
                'disk' => 'public',
            ],
            'generated_conversions' => [],
            'responsive_images' => [],
            'order_column' => 1,
            'visibility' => 'public',
            'file_path' => 'employee-profile/'.$collection,
            'folder_id' => null,
            'file_id' => null,
        ]));
    }

    private function createQualification(Company $company, string $globalId): Qualification
    {
        return Qualification::query()->forceCreate([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'global_id' => $globalId,
            'country_id' => (string) Str::uuid(),
            'university_id' => (string) Str::uuid(),
            'academic_qualification_id' => (string) Str::uuid(),
            'academic_specialization_id' => (string) Str::uuid(),
            'study_rate' => 90,
            'graduation_date' => now()->toDateString(),
        ]);
    }

    private function createCourse(Company $company, string $globalId): UserEducationalCourse
    {
        return UserEducationalCourse::query()->forceCreate([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'global_id' => $globalId,
            'name' => 'Safety Course',
        ]);
    }

    private function createProfessionalCertificate(Company $company, string $globalId): ProfessionalCertificate
    {
        return ProfessionalCertificate::query()->forceCreate([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'global_id' => $globalId,
            'professional_bodie_id' => (string) Str::uuid(),
            'accreditation_name' => 'PMP',
            'date_obtain' => now()->subYear()->toDateString(),
            'date_end' => now()->addYear()->toDateString(),
        ]);
    }

    private function createJobOffer(Company $company, string $globalId): JobOffer
    {
        return JobOffer::query()->forceCreate([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'global_id' => $globalId,
            'job_offer_number' => 'JO-'.Str::upper(Str::random(6)),
            'date_send' => now()->subDays(10)->toDateString(),
            'date_accept' => now()->subDays(5)->toDateString(),
        ]);
    }

    private function createEmploymentContract(Company $company, string $globalId): EmploymentContract
    {
        return EmploymentContract::query()->forceCreate([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'global_id' => $globalId,
            'contract_number' => 'EC-'.Str::upper(Str::random(6)),
            'start_date' => now()->toDateString(),
            'commencement_date' => now()->toDateString(),
            'contract_duration' => '12',
            'notice_period' => 30,
            'probation_period' => 90,
            'nature_work' => 'Full time',
            'type_working_hours' => 'Fixed',
            'working_hours' => 8,
            'annual_leave' => 21,
            'country_id' => (string) Str::uuid(),
            'right_terminate' => 'standard',
        ]);
    }

    private function databaseReady(): bool
    {
        try {
            return Schema::hasTable('companies')
                && Schema::hasTable('users')
                && Schema::hasTable('company_users')
                && Schema::hasTable('company_users_companies')
                && Schema::hasTable('folders')
                && Schema::hasTable('files')
                && Schema::hasTable('media')
                && Schema::hasTable('qualifications')
                && Schema::hasTable('user_educational_courses')
                && Schema::hasTable('professional_certificates')
                && Schema::hasTable('job_offers')
                && Schema::hasTable('employment_contracts');
        } catch (Throwable) {
            return false;
        }
    }
}
