<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Country\Models\Country;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

class ProjectStampTest extends TestCase
{
    use DatabaseTransactions;

    protected User $actor;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->schemaReady()) {
            $this->markTestSkipped('Project stamp schema is not migrated.');
        }

        Storage::fake('public');
        config([
            'media-library.disk_name' => 'public',
            'media-library.queue_conversions_by_default' => false,
        ]);

        $this->setUpTenantAndActor();
        $this->grantPermissions([
            Permission::PROJECT_MANAGEMENT_CREATE(),
            Permission::PROJECT_MANAGEMENT_UPDATE(),
            Permission::PROJECT_MANAGEMENT_VIEW(),
        ], $this->actor);
    }

    public function test_authorized_user_can_upload_a_valid_stamp(): void
    {
        $project = $this->createProject();

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post("/api/v1/projects/{$project->id}/stamp", [
                'stamp' => UploadedFile::fake()->image('stamp.png'),
            ]);

        $response->assertOk();
        $stampUrl = $response->json('payload.stamp');

        $this->assertNotEmpty($stampUrl);
        $this->assertStringNotContainsString('default_path', $stampUrl);
        $this->assertStringNotContainsString('//storage/', $stampUrl);
        $this->assertSame(1, $project->fresh()->getMedia(ProjectManagement::STAMP_COLLECTION)->count());
        Storage::disk('public')->assertExists(
            $project->fresh()->getFirstMedia(ProjectManagement::STAMP_COLLECTION)->getPathRelativeToRoot()
        );
    }

    public function test_user_with_only_create_permission_can_upload_stamp(): void
    {
        $user = $this->createUser();
        $this->grantPermissions([
            Permission::PROJECT_MANAGEMENT_CREATE(),
            Permission::PROJECT_MANAGEMENT_VIEW(),
        ], $user);

        $project = $this->createProject();

        $this->actingAs($user, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post("/api/v1/projects/{$project->id}/stamp", [
                'stamp' => UploadedFile::fake()->image('stamp.jpg'),
            ])
            ->assertOk();

        $this->assertNotEmpty(
            $project->fresh()->getFirstMediaUrl(ProjectManagement::STAMP_COLLECTION)
        );
    }

    public function test_unauthorized_user_cannot_upload_stamp(): void
    {
        $user = $this->createUser();
        $this->grantPermissions([
            Permission::PROJECT_MANAGEMENT_VIEW(),
        ], $user);

        $project = $this->createProject();

        $this->actingAs($user, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post("/api/v1/projects/{$project->id}/stamp", [
                'stamp' => UploadedFile::fake()->image('stamp.png'),
            ])
            ->assertForbidden();
    }

    public function test_invalid_stamp_file_is_rejected(): void
    {
        $project = $this->createProject();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post("/api/v1/projects/{$project->id}/stamp", [
                'stamp' => UploadedFile::fake()->create('stamp.pdf', 100, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['stamp']);
    }

    public function test_uploading_a_new_stamp_replaces_the_old_one(): void
    {
        $project = $this->createProject();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post("/api/v1/projects/{$project->id}/stamp", [
                'stamp' => UploadedFile::fake()->image('old-stamp.png'),
            ])
            ->assertOk();

        $oldMediaId = $project->fresh()->getFirstMedia(ProjectManagement::STAMP_COLLECTION)?->id;

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post("/api/v1/projects/{$project->id}/stamp", [
                'stamp' => UploadedFile::fake()->image('new-stamp.webp'),
            ])
            ->assertOk();

        $project = $project->fresh();
        $media = $project->getMedia(ProjectManagement::STAMP_COLLECTION);

        $this->assertCount(1, $media);
        $this->assertNotSame($oldMediaId, $media->first()?->id);
    }

    public function test_get_stamp_returns_url_when_present(): void
    {
        $project = $this->createProject();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post("/api/v1/projects/{$project->id}/stamp", [
                'stamp' => UploadedFile::fake()->image('stamp.jpeg'),
            ])
            ->assertOk();

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/stamp")
            ->assertOk();

        $this->assertIsString($response->json('payload.stamp'));
        $this->assertNotSame('', $response->json('payload.stamp'));
    }

    public function test_get_stamp_returns_null_when_missing(): void
    {
        $project = $this->createProject();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/stamp")
            ->assertOk()
            ->assertJsonPath('payload.stamp', null);
    }

    public function test_project_details_include_stamp(): void
    {
        $project = $this->createProject();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('payload.stamp', null);

        $upload = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post("/api/v1/projects/{$project->id}/stamp", [
                'stamp' => UploadedFile::fake()->image('details-stamp.png'),
            ]);

        $upload->assertOk();
        $stampUrl = $upload->json('payload.stamp');

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('payload.stamp', $stampUrl);
    }

    public function test_cannot_access_stamp_of_project_from_another_tenant(): void
    {
        $otherCompany = $this->createCompany('Other Stamp Tenant');
        $otherProject = $this->createProject(['company_id' => $otherCompany->id]);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/{$otherProject->id}/stamp")
            ->assertNotFound();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->post("/api/v1/projects/{$otherProject->id}/stamp", [
                'stamp' => UploadedFile::fake()->image('foreign-stamp.png'),
            ])
            ->assertNotFound();
    }

    private function schemaReady(): bool
    {
        return Schema::hasTable('projects')
            && Schema::hasColumn('projects', 'stamp')
            && Schema::hasTable('media')
            && Schema::hasTable('project_types')
            && Schema::hasTable('companies')
            && Schema::hasTable('permissions')
            && Schema::hasTable('users');
    }

    private function setUpTenantAndActor(): void
    {
        $this->company = $this->createCompany('Project Stamp Test Company');
        tenancy()->initialize($this->company);

        $this->actor = $this->createUser();
    }

    private function createCompany(string $name): Company
    {
        $country = Country::query()->first()
            ?? Country::query()->create([
                'name' => 'Project Stamp Test Country',
                'phonecode' => '20',
                'status' => 1,
            ]);

        $slug = Str::lower(Str::random(6));

        $company = Company::withoutEvents(fn () => Company::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => $name, 'ar' => $name],
            'user_name' => 'project_stamp_'.$slug,
            'email' => 'project-stamp-'.$slug.'@example.test',
            'phone' => '01000000000',
            'country_id' => $country->id,
            'company_type_id' => (string) Str::uuid(),
            'company_field_id' => (string) Str::uuid(),
            'registration_type_id' => (string) Str::uuid(),
            'general_manager_id' => (string) Str::uuid(),
            'is_active' => 1,
            'complete_data' => 1,
            'serial_no' => 'PROJECT-STAMP-'.Str::upper(Str::random(8)),
        ]));

        $company->domains()->firstOrCreate(['domain' => 'project-stamp-'.$slug.'.test']);

        return $company;
    }

    private function createUser(): User
    {
        return User::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    private function createProject(array $overrides = []): ProjectManagement
    {
        $projectTypeId = $this->projectTypeId();

        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->withoutGlobalScopes()->forceCreate(array_merge([
            'id' => (string) Str::uuid(),
            'project_type_id' => $projectTypeId,
            'sub_project_type_id' => $projectTypeId,
            'sub_sub_project_type_id' => $projectTypeId,
            'name' => 'Project Stamp Existing Project',
            'company_id' => $this->company->id,
            'status' => 1,
            'serial_number' => 'STAMP-'.Str::upper(Str::random(6)),
        ], $overrides)));
    }

    private function projectTypeId(): int
    {
        return (int) ProjectType::query()->withoutGlobalScopes()->firstOrCreate(
            [
                'name' => 'Project Stamp Test Type',
                'company_id' => $this->company->id,
            ],
            [
                'is_created' => true,
                'is_have_schema' => false,
                'is_active' => true,
            ],
        )->id;
    }

    private function grantPermissions(array $permissions, User $user): void
    {
        setPermissionsTeamId($this->company->id);

        foreach ($permissions as $permission) {
            SpatiePermission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'api'],
                ['name' => $permission, 'guard_name' => 'api', 'company_id' => $this->company->id],
            );
        }

        $user->givePermissionTo($permissions);
    }
}
