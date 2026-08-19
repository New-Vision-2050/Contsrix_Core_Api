<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Mockery;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Shared\PCloud\Services\PCloudClient;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

class ProjectPCloudConnectionTest extends BaseAttendanceReportTestCase
{
    public function test_route_is_protected_by_auth_and_tenant_middleware(): void
    {
        $route = collect(Route::getRoutes())
            ->first(fn ($route): bool => $route->uri() === 'api/v1/projects/pcloud-sync/test');

        $this->assertNotNull($route);
        $this->assertContains('auth:api', $route->middleware());
        $this->assertContains(InitializeTenancyByRequestData::class, $route->middleware());

        $this->postJson('/api/v1/projects/pcloud-sync/test')
            ->assertStatus(401);
    }

    public function test_it_uploads_a_test_file_from_the_server_and_returns_its_location(): void
    {
        $client = Mockery::mock(PCloudClient::class);
        $client->shouldReceive('isConfigured')->once()->andReturnTrue();
        $client->shouldReceive('ensureFolderPath')
            ->once()
            ->with(Mockery::on(static fn (string $path): bool => str_starts_with(
                $path,
                'Constrix Archive/__server-pcloud-test__/',
            )))
            ->andReturn(123);
        $client->shouldReceive('uploadFile')
            ->once()
            ->with(
                123,
                Mockery::on(static fn (string $filename): bool => str_starts_with($filename, 'pcloud-test-')
                    && str_ends_with($filename, '.txt')),
                Mockery::type('string'),
                'text/plain',
            )
            ->andReturn([
                'skipped' => false,
                'metadata' => [['fileid' => 456]],
            ]);
        $this->app->instance(PCloudClient::class, $client);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/pcloud-sync/test');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'pCloud server test completed')
            ->assertJsonPath('payload.folder_id', 123)
            ->assertJsonPath('payload.file_id', 456)
            ->assertJsonPath('payload.skipped', false);

        $this->assertStringStartsWith(
            'Constrix Archive/__server-pcloud-test__/',
            (string) $response->json('payload.path'),
        );
    }
}
