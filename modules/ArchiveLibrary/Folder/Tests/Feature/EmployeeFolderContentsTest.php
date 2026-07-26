<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\ArchiveLibrary\File\Services\EmployeeArchiveFileService;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\Company\CompanyCore\Models\Company;
use Modules\User\Models\User;
use Tests\TestCase;

class EmployeeFolderContentsTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;

    private Company $otherCompany;

    private User $actor;

    private string $employeeGlobalId;

    private string $otherEmployeeGlobalId;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->databaseReady()) {
            $this->markTestSkipped('Database seed prerequisites missing for employee folder contents feature tests.');
        }

        $this->company = $this->createCompany('employee-documents');
        $this->otherCompany = $this->createCompany('other-employee-documents');

        tenancy()->initialize($this->company);

        $this->actor = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->employeeGlobalId = (string) Str::uuid();
        $this->otherEmployeeGlobalId = (string) Str::uuid();
    }

    public function test_employee_root_returns_only_direct_employee_folders(): void
    {
        $tree = $this->seedEmployeeArchiveTree();

        $response = $this->getEmployeeContents();

        $response->assertOk();

        $this->assertEqualsCanonicalizing(
            ['عمرو محمد', 'سارة علي'],
            $this->names($response->json('payload.folders'))
        );
        $this->assertSame([], $this->names($response->json('payload.files')));
        $this->assertNotContains('Screenshot', $this->names($response->json('payload.files')));
        $this->assertNotContains($tree['other_company_root']->name, $this->names($response->json('payload.folders')));
    }

    public function test_employee_folder_returns_only_direct_children(): void
    {
        $tree = $this->seedEmployeeArchiveTree();

        $response = $this->getEmployeeContents(['parent_id' => $tree['employee_root']->id]);

        $response->assertOk();

        $this->assertEqualsCanonicalizing(
            [
                EmployeeArchiveFileService::SECTION_PERSONAL,
                EmployeeArchiveFileService::SECTION_ACADEMIC,
                EmployeeArchiveFileService::SECTION_EMPLOYMENT,
            ],
            $this->names($response->json('payload.folders'))
        );
        $this->assertSame([], $this->names($response->json('payload.files')));
        $this->assertNotContains(EmployeeArchiveFileService::SUB_COURSES, $this->names($response->json('payload.folders')));
    }

    public function test_employee_section_and_subsection_return_only_their_direct_contents(): void
    {
        $tree = $this->seedEmployeeArchiveTree();

        $sectionResponse = $this->getEmployeeContents(['parent_id' => $tree['academic_section']->id]);

        $sectionResponse->assertOk();
        $this->assertEqualsCanonicalizing(
            [
                EmployeeArchiveFileService::SUB_QUALIFICATION,
                EmployeeArchiveFileService::SUB_COURSES,
                EmployeeArchiveFileService::SUB_CV,
            ],
            $this->names($sectionResponse->json('payload.folders'))
        );
        $this->assertSame(['Academic Direct File'], $this->names($sectionResponse->json('payload.files')));

        $subsectionResponse = $this->getEmployeeContents(['parent_id' => $tree['courses_subsection']->id]);

        $subsectionResponse->assertOk();
        $this->assertSame([], $this->names($subsectionResponse->json('payload.folders')));
        $this->assertSame(['Screenshot'], $this->names($subsectionResponse->json('payload.files')));
        $this->assertNotContains('Other Employee Screenshot', $this->names($subsectionResponse->json('payload.files')));
    }

    public function test_employee_files_with_null_parent_appear_only_at_employee_root(): void
    {
        $tree = $this->seedEmployeeArchiveTree();
        $this->createEmployeeFile('Root Employee File', null, $this->employeeGlobalId, $this->company->id);

        $rootResponse = $this->getEmployeeContents();
        $employeeFolderResponse = $this->getEmployeeContents(['parent_id' => $tree['employee_root']->id]);

        $rootResponse->assertOk();
        $employeeFolderResponse->assertOk();

        $this->assertSame(['Root Employee File'], $this->names($rootResponse->json('payload.files')));
        $this->assertNotContains('Root Employee File', $this->names($employeeFolderResponse->json('payload.files')));
    }

    public function test_employee_root_pagination_counts_only_visible_items(): void
    {
        $this->seedEmployeeArchiveTree();

        $response = $this->getEmployeeContents([
            'page' => 1,
            'per_page' => 1,
        ]);

        $response->assertOk()
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('pagination.per_page', 1)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.last_page', 2);

        $this->assertCount(1, $this->names($response->json('payload.folders')));
        $this->assertSame([], $this->names($response->json('payload.files')));
    }

    public function test_sorting_is_limited_to_the_current_employee_folder_level(): void
    {
        $tree = $this->seedEmployeeArchiveTree();
        $this->createEmployeeFile('Alpha Course', $tree['courses_subsection']->id, $this->employeeGlobalId, $this->company->id);
        $this->createEmployeeFile('Zulu Course', $tree['courses_subsection']->id, $this->employeeGlobalId, $this->company->id);

        $rootResponse = $this->getEmployeeContents(['sort' => 'desc']);
        $subsectionResponse = $this->getEmployeeContents([
            'parent_id' => $tree['courses_subsection']->id,
            'sort' => 'desc',
        ]);

        $rootResponse->assertOk();
        $subsectionResponse->assertOk();

        $this->assertSame(['عمرو محمد', 'سارة علي'], $this->names($rootResponse->json('payload.folders')));
        $this->assertSame([], $this->names($rootResponse->json('payload.files')));
        $this->assertSame(['Zulu Course', 'Screenshot', 'Alpha Course'], $this->names($subsectionResponse->json('payload.files')));
    }

    public function test_employee_archive_service_attaches_uploads_to_resolved_subsection_folder(): void
    {
        $service = app(EmployeeArchiveFileService::class);

        $archiveFiles = $service->archiveUploadedFiles(
            companyId: $this->company->id,
            employeeGlobalId: $this->employeeGlobalId,
            employeeName: 'عمرو محمد',
            files: UploadedFile::fake()->create('Screenshot.png', 12, 'image/png'),
            mainSection: EmployeeArchiveFileService::SECTION_ACADEMIC,
            subSection: EmployeeArchiveFileService::SUB_COURSES,
        );

        $this->assertCount(1, $archiveFiles);

        $file = $archiveFiles[0]->fresh();
        $subsection = Folder::query()
            ->where('type', EmployeeArchiveFileService::TYPE_EMPLOYEE)
            ->where('employee_global_id', $this->employeeGlobalId)
            ->where('name', EmployeeArchiveFileService::SUB_COURSES)
            ->firstOrFail();

        $this->assertSame($subsection->id, $file->folder_id);
    }

    private function seedEmployeeArchiveTree(): array
    {
        $employeeRoot = $this->createEmployeeFolder('عمرو محمد', null, $this->employeeGlobalId, $this->company->id);
        $otherEmployeeRoot = $this->createEmployeeFolder('سارة علي', null, $this->otherEmployeeGlobalId, $this->company->id);
        $otherCompanyRoot = $this->createEmployeeFolder('شركة أخرى', null, (string) Str::uuid(), $this->otherCompany->id);

        $personalSection = $this->createEmployeeFolder(
            EmployeeArchiveFileService::SECTION_PERSONAL,
            $employeeRoot->id,
            $this->employeeGlobalId,
            $this->company->id
        );
        $academicSection = $this->createEmployeeFolder(
            EmployeeArchiveFileService::SECTION_ACADEMIC,
            $employeeRoot->id,
            $this->employeeGlobalId,
            $this->company->id
        );
        $employmentSection = $this->createEmployeeFolder(
            EmployeeArchiveFileService::SECTION_EMPLOYMENT,
            $employeeRoot->id,
            $this->employeeGlobalId,
            $this->company->id
        );

        $qualificationSubsection = $this->createEmployeeFolder(
            EmployeeArchiveFileService::SUB_QUALIFICATION,
            $academicSection->id,
            $this->employeeGlobalId,
            $this->company->id
        );
        $coursesSubsection = $this->createEmployeeFolder(
            EmployeeArchiveFileService::SUB_COURSES,
            $academicSection->id,
            $this->employeeGlobalId,
            $this->company->id
        );
        $cvSubsection = $this->createEmployeeFolder(
            EmployeeArchiveFileService::SUB_CV,
            $academicSection->id,
            $this->employeeGlobalId,
            $this->company->id
        );

        $otherEmployeeSection = $this->createEmployeeFolder(
            EmployeeArchiveFileService::SECTION_ACADEMIC,
            $otherEmployeeRoot->id,
            $this->otherEmployeeGlobalId,
            $this->company->id
        );
        $otherEmployeeSubsection = $this->createEmployeeFolder(
            EmployeeArchiveFileService::SUB_COURSES,
            $otherEmployeeSection->id,
            $this->otherEmployeeGlobalId,
            $this->company->id
        );

        $this->createEmployeeFile('Screenshot', $coursesSubsection->id, $this->employeeGlobalId, $this->company->id);
        $this->createEmployeeFile('Academic Direct File', $academicSection->id, $this->employeeGlobalId, $this->company->id);
        $this->createEmployeeFile('Other Employee Screenshot', $otherEmployeeSubsection->id, $this->otherEmployeeGlobalId, $this->company->id);
        $this->createEmployeeFile('Other Company Screenshot', $otherCompanyRoot->id, (string) Str::uuid(), $this->otherCompany->id);

        Folder::query()->withoutTenancy()->create([
            'id' => (string) Str::uuid(),
            'name' => 'General Library',
            'parent_id' => null,
            'project_id' => null,
            'access_type' => 'public',
            'status' => 1,
            'type' => null,
            'company_id' => $this->company->id,
        ]);

        File::query()->withoutTenancy()->create([
            'id' => (string) Str::uuid(),
            'name' => 'General Root File',
            'reference_number' => (string) Str::uuid(),
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'folder_id' => null,
            'project_id' => null,
            'access_type' => 'public',
            'status' => 1,
            'company_id' => $this->company->id,
            'type' => null,
        ]);

        return [
            'employee_root' => $employeeRoot,
            'other_employee_root' => $otherEmployeeRoot,
            'other_company_root' => $otherCompanyRoot,
            'personal_section' => $personalSection,
            'academic_section' => $academicSection,
            'employment_section' => $employmentSection,
            'qualification_subsection' => $qualificationSubsection,
            'courses_subsection' => $coursesSubsection,
            'cv_subsection' => $cvSubsection,
        ];
    }

    private function createEmployeeFolder(string $name, ?string $parentId, string $employeeGlobalId, string $companyId): Folder
    {
        return Folder::query()->withoutTenancy()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'parent_id' => $parentId,
            'project_id' => null,
            'access_type' => 'public',
            'status' => 1,
            'type' => EmployeeArchiveFileService::TYPE_EMPLOYEE,
            'employee_global_id' => $employeeGlobalId,
            'company_id' => $companyId,
        ]);
    }

    private function createEmployeeFile(string $name, ?string $folderId, string $employeeGlobalId, string $companyId): File
    {
        return File::query()->withoutTenancy()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'reference_number' => (string) Str::uuid(),
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'folder_id' => $folderId,
            'project_id' => null,
            'access_type' => 'public',
            'status' => 1,
            'company_id' => $companyId,
            'type' => EmployeeArchiveFileService::TYPE_EMPLOYEE,
            'employee_global_id' => $employeeGlobalId,
            'employee_section' => EmployeeArchiveFileService::SECTION_ACADEMIC,
            'employee_sub_section' => EmployeeArchiveFileService::SUB_COURSES,
        ]);
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
            'serial_no' => 'EMP-DOC-'.Str::upper(Str::random(8)),
        ]));
    }

    private function getEmployeeContents(array $query = [])
    {
        return $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/folders/contents?'.http_build_query(array_merge([
                'type' => 'employee',
                'page' => 1,
                'per_page' => 10,
            ], $query)));
    }

    private function names(?array $items): array
    {
        return array_column($items ?? [], 'name');
    }

    private function databaseReady(): bool
    {
        try {
            return Schema::hasTable('companies')
                && Schema::hasTable('users')
                && Schema::hasTable('folders')
                && Schema::hasTable('files')
                && Schema::hasTable('media');
        } catch (\Throwable) {
            return false;
        }
    }
}
