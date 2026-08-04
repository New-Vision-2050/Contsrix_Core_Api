<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Project\ProjectManagement\Models\ProjectContractor;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectType\Models\OrderPermitDepartment;
use Modules\Project\ProjectType\Models\ProjectManagement as OrderPermitManagement;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\User\Models\User;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Tests\TestCase;

final class SafetySearchTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->tablesReady()) {
            $this->markTestSkipped('Required tables are missing. Run migrations before executing this suite.');
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

        $this->actor = $this->createProjectUser('Safety Search Actor');
        $this->assignee = $this->createProjectUser('Safety Search Assignee');
        $this->assignToProject($this->assignee);
    }

    public function test_search_finds_order_permit_by_name(): void
    {
        $contractor = ProjectContractor::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
            'name' => 'شركة منصور المساعد',
            'number' => 'CTR-SEARCH-'.Str::upper(Str::random(4)),
            'is_active' => true,
        ]);

        $department = OrderPermitDepartment::query()->firstOrCreate(
            ['name' => 'قسم التوصيلات'],
        );

        $management = OrderPermitManagement::query()->create([
            'project_id' => $this->project->id,
            'name' => 'إدارة المشاريع',
        ]);

        $assignedDate = Carbon::today()->subDays(13)->toDateString();

        $permit = ProjectOrderPermit::query()->create([
            'project_id' => $this->project->id,
            'project_management_id' => $management->id,
            'order_permit_department_id' => $department->id,
            'contractor_id' => $contractor->id,
            'name' => '242038555',
            'type' => 'إنشاء',
            'assigned_date' => $assignedDate,
            'price' => 150000.00,
            'contractor_work_order_status' => 'تم الاستلام من المقاول',
            'executing_entity' => 'جهة تنفيذ اختبار',
            'office' => 'مكتب الرياض',
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/safety/search?q=242038555');

        $response->assertOk();
        $payload = $response->json('payload');

        $this->assertSame('order_permit', $payload['type']);
        $this->assertSame($permit->id, $payload['item']['id']);
        $this->assertSame('242038555', $payload['item']['number']);
        $this->assertSame('242038555', $payload['item']['permit_number']);
        $this->assertSame($contractor->id, $payload['item']['contractor']['id']);
        $this->assertSame('شركة منصور المساعد', $payload['item']['contractor']['name']);
        $this->assertSame('جهة تنفيذ اختبار', $payload['item']['department']);
        $this->assertSame('إنشاء', $payload['item']['type']);
        $this->assertSame('مكتب الرياض', $payload['item']['management']);
        $this->assertSame($assignedDate, $payload['item']['assignment_date']);
        $this->assertSame(13, $payload['item']['days_since_assignment']);
        $this->assertEquals('150000.00', (string) $payload['item']['price']);
        $this->assertSame('تم الاستلام من المقاول', $payload['item']['payment_status']);
    }

    public function test_search_finds_notification_by_number(): void
    {
        $contractor = ProjectContractor::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
            'name' => 'Notification Contractor',
            'number' => 'CTR-NTF-'.Str::upper(Str::random(4)),
            'is_active' => true,
        ]);

        $number = 'NTF-SEARCH-'.Str::upper(Str::random(6));
        $taskDate = Carbon::today()->subDays(5)->toDateString();

        $notification = ProjectNotification::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
            'notification_number' => $number,
            'notification_type' => 'صيانة',
            'work_type' => 'صيانة وقائية',
            'status' => 'pending',
            'created_by_user_id' => $this->actor->id,
            'contractor_id' => $contractor->id,
            'assigned_user_ids' => [(string) $this->assignee->id],
            'task_date' => $taskDate,
            'work_description' => 'Searchable notification',
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/safety/search?q='.$number);

        $response->assertOk();
        $payload = $response->json('payload');

        $this->assertSame('notification', $payload['type']);
        $this->assertSame($notification->id, $payload['item']['id']);
        $this->assertSame($number, $payload['item']['number']);
        $this->assertSame($contractor->id, $payload['item']['contractor']['id']);
        $this->assertSame('صيانة وقائية', $payload['item']['department']);
        $this->assertSame('صيانة', $payload['item']['type']);
        $this->assertSame('Searchable notification', $payload['item']['management']);
        $this->assertSame($this->assignee->name, $payload['item']['assigned_engineer']);
        $this->assertSame($taskDate, $payload['item']['assignment_date']);
        $this->assertSame(5, $payload['item']['days_since_assignment']);
        $this->assertSame('pending', $payload['item']['permit_status']);
        $this->assertNull($payload['item']['price']);
    }

    public function test_search_returns_404_when_not_found(): void
    {
        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/safety/search?q=DOES-NOT-EXIST-999');

        $response->assertStatus(404);
    }

    public function test_search_requires_query_parameter(): void
    {
        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/safety/search');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['q']);
    }

    private function tablesReady(): bool
    {
        try {
            return Schema::hasTable('project_notifications')
                && Schema::hasTable('project_order_permit')
                && Schema::hasTable('project_contractors')
                && Schema::hasTable('project_managements')
                && Schema::hasTable('order_permit_department')
                && Schema::hasTable('projects')
                && Schema::hasTable('companies')
                && Schema::hasTable('users');
        } catch (\Throwable) {
            return false;
        }
    }

    private function ensureHierarchy(): void
    {
        $this->branch = ManagementHierarchy::withoutEvents(fn () => ManagementHierarchy::query()->firstOrCreate(
            [
                'company_id' => $this->company->id,
                'type' => 'branch',
                'name' => 'Safety Search Branch',
            ],
            ['id' => (string) Str::uuid()]
        ));

        $this->management = ManagementHierarchy::withoutEvents(fn () => ManagementHierarchy::query()->firstOrCreate(
            [
                'company_id' => $this->company->id,
                'type' => 'management',
                'name' => 'Safety Search Management',
            ],
            ['id' => (string) Str::uuid()]
        ));

        $this->department = ManagementHierarchy::withoutEvents(fn () => ManagementHierarchy::query()->firstOrCreate(
            [
                'company_id' => $this->company->id,
                'type' => 'department',
                'name' => 'Safety Search Department',
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
