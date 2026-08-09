<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Project\ProjectManagement\Models\ProjectContractor;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectType\Models\SafetyRecord;
use Modules\Project\ProjectType\Models\Violation;
use Modules\User\Models\User;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Tests\TestCase;
use ZipArchive;

final class SafetyViolationReportTest extends TestCase
{
    use DatabaseTransactions;

    private const EXISTING_PROJECT_ID = '9a79b5b5-7e91-11f1-817a-bce92f8cda2e';

    private Company $company;

    private ProjectManagement $project;

    private User $actor;

    private User $assignee;

    private ManagementHierarchy $branch;

    private ManagementHierarchy $management;

    private ManagementHierarchy $department;

    private ProjectNotification $projectNotification;

    private ProjectContractor $contractor;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->safetyTablesReady()) {
            $this->markTestSkipped('Safety tables are missing. Run migrations before executing this suite.');
        }

        $this->project = ProjectManagement::withoutGlobalScopes()->find(self::EXISTING_PROJECT_ID);

        if (! $this->project) {
            $this->markTestSkipped('Existing project '.self::EXISTING_PROJECT_ID.' was not found.');
        }

        $this->company = Company::withoutGlobalScopes()->find($this->project->company_id);

        if (! $this->company) {
            $this->markTestSkipped('Company for existing project was not found.');
        }

        tenancy()->initialize($this->company);

        $this->project = ProjectManagement::withoutGlobalScopes()->find(self::EXISTING_PROJECT_ID)
            ?? $this->project;

        $this->ensureHierarchy();

        $this->actor = $this->createProjectUser('Report Actor');
        $this->assignee = $this->createProjectUser('Report Assignee');
        $this->assignToProject($this->assignee);

        $this->contractor = ProjectContractor::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
            'name' => 'Test Contractor Co',
            'is_active' => true,
        ]);

        $this->projectNotification = ProjectNotification::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
            'notification_number' => 'NTF-RPT-'.Str::upper(Str::random(6)),
            'status' => 'pending',
            'created_by_user_id' => $this->actor->id,
            'district' => 'الربوة',
            'contractor_id' => $this->contractor->id,
            'work_description' => 'Violation report parent',
        ]);
    }

    public function test_one_found_violation_returns_single_pdf(): void
    {
        $violation = $this->createViolation('RPT-1', 'A', 7);
        $record = $this->createCompletedRecord([
            $violation->id => ['weight' => -7, 'status' => 'violation_found', 'action' => null],
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->get('/api/v1/projects/'.$this->project->id.'/safety/'.$record->id.'/violation-report');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_two_found_violations_returns_single_pdf(): void
    {
        $one = $this->createViolation('RPT-2A', 'B', 2);
        $two = $this->createViolation('RPT-2B', 'B', 2);
        $record = $this->createCompletedRecord([
            $one->id => ['weight' => -2000, 'status' => 'violation_found', 'action' => 'stop_work'],
            $two->id => ['weight' => -2000, 'status' => 'violation_found', 'action' => null],
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->get('/api/v1/projects/'.$this->project->id.'/safety/'.$record->id.'/violation-report');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_three_found_violations_returns_zip_with_two_pdfs(): void
    {
        $one = $this->createViolation('RPT-3A', 'A', 7);
        $two = $this->createViolation('RPT-3B', 'B', 2);
        $three = $this->createViolation('RPT-3C', 'C', 0.5);
        $record = $this->createCompletedRecord([
            $one->id => ['weight' => -7, 'status' => 'violation_found', 'action' => null],
            $two->id => ['weight' => -2, 'status' => 'violation_found', 'action' => null],
            $three->id => ['weight' => -0.5, 'status' => 'violation_found', 'action' => null],
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->get('/api/v1/projects/'.$this->project->id.'/safety/'.$record->id.'/violation-report');

        $response->assertOk();
        $this->assertSame('application/zip', $response->headers->get('content-type'));

        $content = $response->baseResponse instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse
            ? file_get_contents($response->baseResponse->getFile()->getPathname())
            : $response->getContent();

        $tmpZip = tempnam(sys_get_temp_dir(), 'svr').'.zip';
        file_put_contents($tmpZip, $content);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($tmpZip) === true);
        $this->assertSame(2, $zip->numFiles);

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();
        @unlink($tmpZip);

        sort($names);
        $this->assertSame([
            'safety-violation-report-'.$record->id.'-1.pdf',
            'safety-violation-report-'.$record->id.'-2.pdf',
        ], $names);
    }

    public function test_non_found_statuses_are_excluded(): void
    {
        $found = $this->createViolation('RPT-4A', 'A', 7);
        $none = $this->createViolation('RPT-4B', 'B', 2);
        $na = $this->createViolation('RPT-4C', 'C', 0.5);

        $record = $this->createCompletedRecord([
            $found->id => ['weight' => -7, 'status' => 'violation_found', 'action' => null],
            $none->id => ['weight' => 2, 'status' => 'no_violation', 'action' => null],
            $na->id => ['weight' => 0, 'status' => 'not_applicable', 'action' => null],
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->get('/api/v1/projects/'.$this->project->id.'/safety/'.$record->id.'/violation-report');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_no_found_violations_returns_validation_error(): void
    {
        $none = $this->createViolation('RPT-5A', 'B', 2);
        $record = $this->createCompletedRecord([
            $none->id => ['weight' => 2, 'status' => 'no_violation', 'action' => null],
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/'.$this->project->id.'/safety/'.$record->id.'/violation-report');

        $response->assertStatus(422);
        $this->assertStringContainsString('مخالفات', (string) $response->json('message.description'));
    }

    public function test_missing_optional_safety_rep_does_not_fail(): void
    {
        $violation = $this->createViolation('RPT-6A', 'B', 2);
        $record = $this->createCompletedRecord([
            $violation->id => ['weight' => -2, 'status' => 'violation_found', 'action' => null],
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->get('/api/v1/projects/'.$this->project->id.'/safety/'.$record->id.'/violation-report');

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_no_evidence_images_does_not_fail(): void
    {
        $violation = $this->createViolation('RPT-7A', 'A', 7);
        $record = $this->createCompletedRecord([
            $violation->id => ['weight' => -7, 'status' => 'violation_found', 'action' => null],
        ]);

        $this->assertCount(0, $record->getMedia('violation_evidence'));

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->get('/api/v1/projects/'.$this->project->id.'/safety/'.$record->id.'/violation-report');

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_missing_safety_record_returns_404(): void
    {
        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/'.$this->project->id.'/safety/'.(string) Str::uuid().'/violation-report');

        $response->assertNotFound();
    }

    private function createCompletedRecord(array $syncData): SafetyRecord
    {
        $record = SafetyRecord::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
            'morphable_type' => 'project_notification',
            'morphable_id' => $this->projectNotification->id,
            'order_type' => 'صيانة',
            'date' => now()->toDateString(),
            'time' => '08:04',
            'contractor_id' => $this->contractor->id,
            'assigned_user_id' => $this->assignee->id,
            'consultant_engineer' => $this->assignee->name,
            'status' => 'completed',
            'required_score' => 1,
            'earned_score' => 0,
            'percentage' => 0,
        ]);

        $record->violations()->sync($syncData);
        $record->load(['violations', 'media']);

        return $record;
    }

    private function createViolation(string $code, string $category, float $weight): Violation
    {
        return Violation::query()->create([
            'id' => (string) Str::uuid(),
            'code' => $code.'-'.Str::upper(Str::random(3)),
            'description' => 'Test violation '.$code,
            'category' => $category,
            'default_weight' => $weight,
            'work_cancellation' => $category === 'A',
            'work_stop' => $category === 'B',
            'equipment_exclusion' => $category === 'B',
        ]);
    }

    private function safetyTablesReady(): bool
    {
        return Schema::hasTable('safety_records')
            && Schema::hasTable('violations')
            && Schema::hasTable('safety_record_violation')
            && Schema::hasTable('project_notifications');
    }

    private function ensureHierarchy(): void
    {
        $this->branch = ManagementHierarchy::withoutEvents(fn () => ManagementHierarchy::query()->firstOrCreate(
            [
                'company_id' => $this->company->id,
                'type' => 'branch',
                'name' => 'Violation Report Test Branch',
            ],
            ['id' => (string) Str::uuid()]
        ));

        $this->management = ManagementHierarchy::withoutEvents(fn () => ManagementHierarchy::query()->firstOrCreate(
            [
                'company_id' => $this->company->id,
                'type' => 'management',
                'name' => 'Violation Report Test Management',
            ],
            ['id' => (string) Str::uuid()]
        ));

        $this->department = ManagementHierarchy::withoutEvents(fn () => ManagementHierarchy::query()->firstOrCreate(
            [
                'company_id' => $this->company->id,
                'type' => 'department',
                'name' => 'Violation Report Test Department',
                'parent_id' => $this->management->id,
            ],
            ['id' => (string) Str::uuid()]
        ));
    }

    private function createProjectUser(string $name): User
    {
        $globalId = (string) Str::uuid();

        $user = User::factory()->create([
            'name' => $name,
            'company_id' => $this->company->id,
            'global_company_user_id' => $globalId,
        ]);

        UserProfessionalData::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'global_id' => $globalId,
            'user_id' => $user->id,
            'branch_id' => $this->branch->id,
            'management_id' => (string) $this->management->id,
            'department_id' => $this->department->id,
            'job_code' => 'EMP-'.Str::upper(Str::random(4)),
        ]);

        return $user;
    }

    private function assignToProject(User $user): ProjectEmployee
    {
        return ProjectEmployee::query()->create([
            'id' => (string) Str::uuid(),
            'project_id' => $this->project->id,
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'assigned_at' => now(),
            'assigned_by_user_id' => $this->actor->id,
        ]);
    }
}
