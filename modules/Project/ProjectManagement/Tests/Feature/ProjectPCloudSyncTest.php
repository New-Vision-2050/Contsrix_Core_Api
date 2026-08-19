<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Project\ProjectManagement\Jobs\ExportProjectPCloudArchiveJob;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectType\Models\ProjectType;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

class ProjectPCloudSyncTest extends BaseAttendanceReportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurePCloud();
    }

    public function test_route_is_protected_by_auth_and_tenant_middleware(): void
    {
        $route = collect(Route::getRoutes())
            ->first(fn ($route): bool => $route->uri() === 'api/v1/projects/{project}/pcloud-sync');

        $this->assertNotNull($route);
        $this->assertContains('auth:api', $route->middleware());
        $this->assertContains(InitializeTenancyByRequestData::class, $route->middleware());

        $this->postJson('/api/v1/projects/'.Str::uuid().'/pcloud-sync')
            ->assertStatus(401);
    }

    public function test_queue_mode_returns_accepted_response_and_dispatches_export_job(): void
    {
        Queue::fake();

        $project = $this->createProject('PCloud Queue Project');

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/pcloud-sync");

        $response->assertAccepted()
            ->assertJsonPath('message', 'PCloud export queued')
            ->assertJsonPath('payload.project_id', (string) $project->id)
            ->assertJsonPath('payload.mode', 'queue')
            ->assertJsonPath('payload.path', 'Constrix Archive/'.$this->company->id.'/المشاريع/PCloud Queue Project/الصيانة والطوارئ');

        $runId = (string) $response->json('payload.run_id');
        $this->assertNotSame('', $runId);

        Queue::assertPushed(ExportProjectPCloudArchiveJob::class, fn (ExportProjectPCloudArchiveJob $job): bool => $job->projectId === (string) $project->id
            && $job->companyId === (string) $this->company->id
            && $job->runId === $runId);
    }

    public function test_short_sync_route_accepts_project_id_in_the_request_body(): void
    {
        Queue::fake();

        $project = $this->createProject('PCloud Short Route Project');

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/pcloud-sync', [
                'project_id' => $project->id,
            ])
            ->assertAccepted()
            ->assertJsonPath('message', 'PCloud export queued')
            ->assertJsonPath('payload.project_id', (string) $project->id);

        Queue::assertPushed(
            ExportProjectPCloudArchiveJob::class,
            fn (ExportProjectPCloudArchiveJob $job): bool => $job->projectId === (string) $project->id
                && $job->companyId === (string) $this->company->id,
        );
    }

    public function test_sync_route_returns_not_found_for_projects_outside_current_tenant(): void
    {
        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/'.Str::uuid().'/pcloud-sync');

        $response->assertNotFound()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message.description', 'Project not found');
    }

    public function test_sync_route_returns_unprocessable_when_pcloud_is_disabled(): void
    {
        config(['pcloud.enabled' => false]);

        $project = $this->createProject('PCloud Disabled Project');

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson("/api/v1/projects/{$project->id}/pcloud-sync");

        $response->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message.description', 'pCloud integration is disabled.');
    }

    private function configurePCloud(string $dispatch = 'queue'): void
    {
        config([
            'pcloud.enabled' => true,
            'pcloud.email' => 'user@example.test',
            'pcloud.password' => 'secret-password',
            'pcloud.root_folder' => 'Constrix Archive',
            'pcloud.dispatch' => $dispatch,
            'pcloud.default_api_host' => 'https://api.pcloud.com',
            'pcloud.timeout' => 5,
        ]);
    }

    private function createProject(string $name): ProjectManagement
    {
        [$projectType, $subProjectType, $subSubProjectType] = $this->createProjectTypes();

        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->forceCreate([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'company_id' => $this->company->id,
            'status' => 1,
            'serial_number' => 'PCLOUD-'.Str::upper(Str::random(12)),
            'project_type_id' => $projectType->id,
            'sub_project_type_id' => $subProjectType->id,
            'sub_sub_project_type_id' => $subSubProjectType->id,
        ]));
    }

    /**
     * @return array{0: ProjectType, 1: ProjectType, 2: ProjectType}
     */
    private function createProjectTypes(): array
    {
        $projectType = ProjectType::query()->create([
            'name' => 'PCloud Main Type',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        $subProjectType = ProjectType::query()->create([
            'name' => 'PCloud Sub Type',
            'parent_id' => $projectType->id,
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        $subSubProjectType = ProjectType::query()->create([
            'name' => 'PCloud Sub Sub Type',
            'parent_id' => $subProjectType->id,
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        return [$projectType, $subProjectType, $subSubProjectType];
    }
}
