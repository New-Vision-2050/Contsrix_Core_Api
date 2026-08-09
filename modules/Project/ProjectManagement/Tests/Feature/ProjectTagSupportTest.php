<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Country\Models\Country;
use Modules\Project\ProjectManagement\Database\Seeders\ProjectTagSeeder;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectTag;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class ProjectTagSupportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $actor;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->schemaReady()) {
            $this->markTestSkipped('Project tag support schema is not migrated.');
        }

        $this->setUpTenantAndActor();
        $this->grantProjectManagementPermissions();
    }

    public function test_project_tag_seeder_creates_techsite_and_is_idempotent(): void
    {
        $this->seed(ProjectTagSeeder::class);
        $this->seed(ProjectTagSeeder::class);

        $this->assertSame(1, ProjectTag::query()->where('code', 'TECHSITE')->count());

        $this->assertDatabaseHas('project_tags', [
            'code' => 'TECHSITE',
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $tag = ProjectTag::query()->where('code', 'TECHSITE')->firstOrFail();
        $this->assertSame('TechSite', $tag->getTranslation('name', 'ar'));
        $this->assertSame('TechSite', $tag->getTranslation('name', 'en'));
    }

    public function test_project_tags_lookup_lists_active_tags_only(): void
    {
        $active = ProjectTag::query()->create([
            'name' => ['ar' => 'TechSite', 'en' => 'TechSite'],
            'code' => 'TECHSITE',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $inactive = ProjectTag::query()->create([
            'name' => ['ar' => 'Hidden Tag', 'en' => 'Hidden Tag'],
            'code' => 'HIDDEN_TAG',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/project-tags')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $active->id,
                'name' => 'TechSite',
                'code' => 'TECHSITE',
                'sort_order' => 1,
            ])
            ->assertJsonMissing([
                'id' => $inactive->id,
            ]);
    }

    public function test_project_creation_accepts_and_returns_project_tag(): void
    {
        $tag = $this->createTag('TECHSITE', 'TechSite');

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects', $this->projectPayload([
                'project_tag_id' => $tag->id,
                'project_owner_type' => 'company',
                'project_owner_id' => $this->company->id,
            ]))
            ->assertOk()
            ->assertJsonPath('payload.project_tag_id', (string) $tag->id)
            ->assertJsonPath('payload.project_tag.id', (string) $tag->id)
            ->assertJsonPath('payload.project_tag.name', 'TechSite')
            ->assertJsonPath('payload.project_tag.code', 'TECHSITE');

        $projectId = $response->json('payload.id');

        $this->assertDatabaseHas('projects', [
            'id' => $projectId,
            'project_tag_id' => $tag->id,
            'project_owner_type' => 'company',
        ]);
    }

    public function test_project_update_changes_project_tag(): void
    {
        $oldTag = $this->createTag('OLD_TAG', 'Old Tag');
        $newTag = $this->createTag('NEW_TAG', 'New Tag');
        $project = $this->createProject(['project_tag_id' => $oldTag->id]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson("/api/v1/projects/{$project->id}", $this->projectPayload([
                'project_tag_id' => $newTag->id,
                'project_owner_type' => 'individual',
                'project_owner_id' => $this->actor->id,
            ]))
            ->assertOk()
            ->assertJsonPath('payload.project_tag_id', (string) $newTag->id)
            ->assertJsonPath('payload.project_tag.id', (string) $newTag->id)
            ->assertJsonPath('payload.project_tag.name', 'New Tag')
            ->assertJsonPath('payload.project_owner_type', 'individual');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'project_tag_id' => $newTag->id,
            'project_owner_type' => 'individual',
        ]);
    }

    public function test_invalid_project_tag_id_fails_validation(): void
    {
        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects', $this->projectPayload([
                'project_tag_id' => (string) Str::uuid(),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['project_tag_id']);
    }

    public function test_existing_project_owner_type_validation_is_unchanged(): void
    {
        $companyResponse = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects', $this->projectPayload([
                'name' => 'Company Owner Project',
                'project_owner_type' => 'company',
                'project_owner_id' => $this->company->id,
            ]));

        $companyResponse->assertOk()
            ->assertJsonPath('payload.project_owner_type', 'company');

        $individualResponse = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects', $this->projectPayload([
                'name' => 'Individual Owner Project',
                'project_owner_type' => 'individual',
                'project_owner_id' => $this->actor->id,
            ]));

        $individualResponse->assertOk()
            ->assertJsonPath('payload.project_owner_type', 'individual');

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects', $this->projectPayload([
                'name' => 'Invalid Owner Project',
                'project_owner_type' => 'public',
                'project_owner_id' => $this->actor->id,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['project_owner_type']);
    }

    private function schemaReady(): bool
    {
        return Schema::hasTable('projects')
            && Schema::hasTable('project_tags')
            && Schema::hasColumn('projects', 'project_tag_id')
            && Schema::hasTable('project_types')
            && Schema::hasTable('translations')
            && Schema::hasTable('countries')
            && Schema::hasTable('companies')
            && Schema::hasTable('permissions')
            && Schema::hasTable('users');
    }

    private function setUpTenantAndActor(): void
    {
        $country = Country::query()->first()
            ?? Country::query()->create([
                'name' => 'Project Tag Test Country',
                'phonecode' => '20',
                'status' => 1,
            ]);

        $this->company = Company::withoutEvents(fn () => Company::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Project Tag Test Company', 'ar' => 'Project Tag Test Company'],
            'user_name' => 'project_tag_'.Str::random(6),
            'email' => 'project-tag-'.Str::random(6).'@example.test',
            'phone' => '01000000000',
            'country_id' => $country->id,
            'company_type_id' => (string) Str::uuid(),
            'company_field_id' => (string) Str::uuid(),
            'registration_type_id' => (string) Str::uuid(),
            'general_manager_id' => (string) Str::uuid(),
            'is_active' => 1,
            'complete_data' => 1,
            'serial_no' => 'PROJECT-TAG-'.Str::upper(Str::random(8)),
        ]));

        $this->company->domains()->firstOrCreate(['domain' => 'project-tag-'.Str::random(6).'.test']);
        tenancy()->initialize($this->company);

        $this->actor = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    private function projectPayload(array $overrides = []): array
    {
        $projectTypeId = $this->projectTypeId();

        return array_merge([
            'project_type_id' => $projectTypeId,
            'sub_project_type_id' => $projectTypeId,
            'sub_sub_project_type_id' => $projectTypeId,
            'name' => 'Project Tag Test',
            'manager_id' => null,
            'branch_id' => null,
            'project_owner_type' => null,
            'project_owner_id' => null,
            'contract_id' => null,
            'contractual_engagement_id' => null,
            'project_tag_id' => null,
            'client_id' => null,
            'project_classification_id' => null,
            'cost_center_branch_id' => null,
            'management_id' => null,
            'currency_id' => null,
            'project_value' => 1000,
            'status' => 1,
        ], $overrides);
    }

    private function createProject(array $overrides = []): ProjectManagement
    {
        $projectTypeId = $this->projectTypeId();

        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->withoutGlobalScopes()->forceCreate(array_merge([
            'id' => (string) Str::uuid(),
            'project_type_id' => $projectTypeId,
            'sub_project_type_id' => $projectTypeId,
            'sub_sub_project_type_id' => $projectTypeId,
            'name' => 'Project Tag Existing Project',
            'company_id' => $this->company->id,
            'status' => 1,
            'serial_number' => 'TAG-'.Str::upper(Str::random(6)),
        ], $overrides)));
    }

    private function createTag(string $code, string $name): ProjectTag
    {
        return ProjectTag::query()->create([
            'name' => ['ar' => $name, 'en' => $name],
            'code' => $code,
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    private function projectTypeId(): int
    {
        return (int) ProjectType::query()->withoutGlobalScopes()->firstOrCreate(
            [
                'name' => 'Project Tag Test Type',
                'company_id' => $this->company->id,
            ],
            [
                'is_created' => true,
                'is_have_schema' => false,
                'is_active' => true,
            ],
        )->id;
    }

    private function grantProjectManagementPermissions(): void
    {
        setPermissionsTeamId($this->company->id);

        $permissions = [
            Permission::PROJECT_MANAGEMENT_CREATE(),
            Permission::PROJECT_MANAGEMENT_UPDATE(),
            Permission::PROJECT_MANAGEMENT_VIEW(),
        ];

        foreach ($permissions as $permission) {
            SpatiePermission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'api'],
                ['name' => $permission, 'guard_name' => 'api', 'company_id' => $this->company->id],
            );
        }

        $this->actor->givePermissionTo($permissions);
    }
}
