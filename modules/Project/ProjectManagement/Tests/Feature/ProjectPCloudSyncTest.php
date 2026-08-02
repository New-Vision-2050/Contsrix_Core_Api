<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Project\ProjectManagement\Jobs\ExportProjectPCloudArchiveJob;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
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
            ->assertJsonPath('payload.path', 'Constrix Archive/PCloud Queue Project/الصيانة والطوارئ');

        $runId = (string) $response->json('payload.run_id');
        $this->assertNotSame('', $runId);

        Queue::assertPushed(ExportProjectPCloudArchiveJob::class, fn (ExportProjectPCloudArchiveJob $job): bool => $job->projectId === (string) $project->id
            && $job->companyId === (string) $this->company->id
            && $job->runId === $runId);
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
        config(['services.pcloud.enabled' => false]);

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
            'services.pcloud.enabled' => true,
            'services.pcloud.email' => 'user@example.test',
            'services.pcloud.password' => 'secret-password',
            'services.pcloud.root_folder' => 'Constrix Archive',
            'services.pcloud.dispatch' => $dispatch,
            'services.pcloud.base_url' => 'https://api.pcloud.com',
            'services.pcloud.timeout' => 5,
        ]);
    }

    private function createProject(string $name): ProjectManagement
    {
        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'company_id' => $this->company->id,
            'status' => 1,
        ]));
    }
}
