<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Mockery;
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
            && $job->runId === $runId
            && $job->projectsCount === 1);
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

    public function test_short_sync_route_queues_every_project_when_project_id_is_omitted(): void
    {
        Queue::fake();

        $firstProject = $this->createProject('First PCloud Batch Project');
        $secondProject = $this->createProject('Second PCloud Batch Project');

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/pcloud-sync')
            ->assertAccepted()
            ->assertJsonPath('message', 'PCloud export queued')
            ->assertJsonPath('payload.projects_count', 2);

        Queue::assertPushed(
            ExportProjectPCloudArchiveJob::class,
            2,
        );
        Queue::assertPushed(
            ExportProjectPCloudArchiveJob::class,
            fn (ExportProjectPCloudArchiveJob $job): bool => in_array(
                $job->projectId,
                [(string) $firstProject->id, (string) $secondProject->id],
                true,
            ) && $job->projectsCount === 2,
        );
    }

    public function test_last_finished_job_sends_the_completion_email_once(): void
    {
        $runId = 'pcloud-email-test';
        Cache::put("pcloud-sync:{$runId}:completed", 0, now()->addDay());

        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $text, \Closure $callback): bool {
                $message = Mockery::mock();
                $message->shouldReceive('to')->once()->with('dev.desoky@gmail.com')->andReturnSelf();
                $message->shouldReceive('subject')->once()->with('PCloud sync finished')->andReturnSelf();
                $callback($message);

                return str_contains($text, 'Run: pcloud-email-test')
                    && str_contains($text, 'Projects: 1');
            });

        (new ExportProjectPCloudArchiveJob(
            projectId: (string) Str::uuid(),
            companyId: (string) $this->company->id,
            runId: $runId,
            projectsCount: 1,
        ))->failed(new \RuntimeException('test failure'));
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
