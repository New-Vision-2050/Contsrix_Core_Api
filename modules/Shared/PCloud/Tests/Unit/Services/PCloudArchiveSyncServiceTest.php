<?php

declare(strict_types=1);

namespace Modules\Shared\PCloud\Tests\Unit\Services;

use Modules\ArchiveLibrary\File\Models\File as ArchiveFile;
use Modules\ArchiveLibrary\Folder\Models\Folder as ArchiveFolder;
use Modules\Shared\Media\Models\CustomMedia;
use Modules\Shared\Media\Observers\CustomMediaObserver;
use Modules\Shared\PCloud\Services\PCloudArchiveSyncService;
use Modules\Shared\PCloud\Services\PCloudClient;
use Modules\Shared\PCloud\Tests\Unit\PCloudTestCase;
use Mockery;

final class PCloudArchiveSyncServiceTest extends PCloudTestCase
{
    private PCloudClient|Mockery\MockInterface $client;

    private PCloudArchiveSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Mockery::mock(PCloudClient::class);
        $this->service = new PCloudArchiveSyncService($this->client);
        $this->app->instance(PCloudClient::class, $this->client);
        $this->app->instance(PCloudArchiveSyncService::class, $this->service);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_should_sync_archive_file_model(): void
    {
        $this->client->shouldReceive('isConfigured')->andReturn(true);

        $this->assertTrue($this->service->shouldSync($this->makeMedia([
            'model_type' => ArchiveFile::class,
        ])));
    }

    public function test_should_sync_archive_folder_model(): void
    {
        $this->client->shouldReceive('isConfigured')->andReturn(true);

        $this->assertTrue($this->service->shouldSync($this->makeMedia([
            'model_type' => ArchiveFolder::class,
        ])));
    }

    public function test_should_sync_when_linked_via_file_id_custom_property(): void
    {
        $this->client->shouldReceive('isConfigured')->andReturn(true);

        $this->assertTrue($this->service->shouldSync($this->makeMedia([
            'model_type' => 'Modules\\Project\\ProjectManagement\\Models\\ProjectNotification',
            'custom_properties' => [
                'file_id' => 'archive-file-uuid',
                'folder_id' => 'archive-folder-uuid',
                'file_path' => 'project-notifications/site-status-updates/test/folder',
            ],
        ])));
    }

    public function test_should_sync_project_notification_path_even_without_ids(): void
    {
        $this->client->shouldReceive('isConfigured')->andReturn(true);

        $this->assertTrue($this->service->shouldSync($this->makeMedia([
            'model_type' => 'Modules\\Project\\ProjectManagement\\Models\\ProjectNotification',
            'custom_properties' => [
                'file_path' => 'project-notifications/fines/abc',
            ],
        ])));
    }

    public function test_should_not_sync_unrelated_media(): void
    {
        $this->client->shouldReceive('isConfigured')->andReturn(true);

        $this->assertFalse($this->service->shouldSync($this->makeMedia([
            'model_type' => 'Modules\\User\\Models\\User',
            'custom_properties' => [
                'file_path' => 'avatars/user',
            ],
        ])));
    }

    public function test_should_not_sync_when_not_configured(): void
    {
        $this->client->shouldReceive('isConfigured')->andReturn(false);

        $this->assertFalse($this->service->shouldSync($this->makeMedia([
            'model_type' => ArchiveFile::class,
        ])));
    }

    public function test_dispatch_sync_is_noop_when_not_configured(): void
    {
        $this->client->shouldReceive('isConfigured')->andReturn(false);
        $this->client->shouldNotReceive('ensureFolderPath');
        $this->client->shouldNotReceive('uploadFile');

        $this->service->dispatchSync($this->makeMedia([
            'id' => 42,
            'model_type' => ArchiveFile::class,
        ]));

        $this->assertTrue(true);
    }

    public function test_sync_media_uploads_png_with_correct_mime_and_does_not_throw(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        $this->client->shouldReceive('isConfigured')->andReturn(true);
        $this->client->shouldReceive('ensureFolderPath')
            ->once()
            ->withArgs(fn (string $path) => str_starts_with($path, 'Constrix Archive/'))
            ->andReturn(555);
        $this->client->shouldReceive('uploadFile')
            ->once()
            ->with(555, 'photo.png', $png, 'image/png')
            ->andReturn([
                'result' => 0,
                'metadata' => [[
                    'fileid' => 1001,
                    'contenttype' => 'image/png',
                ]],
            ]);

        $service = new class($this->client) extends PCloudArchiveSyncService {
            protected function readMediaContents(CustomMedia $media): ?string
            {
                return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
            }
        };

        $service->syncMedia($this->makeMedia([
            'id' => 9,
            'model_type' => ArchiveFile::class,
            'file_name' => 'photo.png',
            'mime_type' => 'image/png',
            'custom_properties' => [
                'file_path' => 'project-notifications/site-status-updates/test/folder',
            ],
        ]));

        $this->assertTrue(true);
    }

    public function test_sync_media_skips_empty_contents_without_throwing(): void
    {
        $this->client->shouldReceive('isConfigured')->andReturn(true);
        $this->client->shouldNotReceive('uploadFile');

        $service = new class($this->client) extends PCloudArchiveSyncService {
            protected function readMediaContents(CustomMedia $media): ?string
            {
                return '';
            }
        };

        $service->syncMedia($this->makeMedia([
            'model_type' => ArchiveFile::class,
            'file_name' => 'empty.bin',
        ]));

        $this->assertTrue(true);
    }

    public function test_custom_media_observer_does_not_throw_when_pcloud_disabled(): void
    {
        $this->client->shouldReceive('isConfigured')->andReturn(false);

        $observer = new CustomMediaObserver();
        $observer->created($this->makeMedia([
            'id' => 11,
            'model_type' => ArchiveFile::class,
        ]));

        $this->assertTrue(true);
    }

    public function test_stringify_company_path_helpers_via_sync_fallback_path(): void
    {
        $this->client->shouldReceive('isConfigured')->andReturn(true);
        $this->client->shouldReceive('ensureFolderPath')
            ->once()
            ->withArgs(function (string $path) {
                // No DB company/project → Unknown Company + stripped project-notifications path
                return str_contains($path, 'Constrix Archive')
                    && str_contains($path, 'Unknown Company')
                    && str_contains($path, 'site-status-updates');
            })
            ->andReturn(1);
        $this->client->shouldReceive('uploadFile')
            ->once()
            ->andReturn(['result' => 0, 'metadata' => [['fileid' => 1]]]);

        $service = new class($this->client) extends PCloudArchiveSyncService {
            protected function readMediaContents(CustomMedia $media): ?string
            {
                return 'hello';
            }
        };

        $service->syncMedia($this->makeMedia([
            'model_type' => 'Modules\\Project\\ProjectManagement\\Models\\ProjectNotificationSiteStatusUpdate',
            'file_name' => 'note.txt',
            'mime_type' => 'text/plain',
            'custom_properties' => [
                // No file_id/folder_id → avoid DB lookups; path fallback still syncs
                'file_path' => 'project-notifications/site-status-updates/PN-1/update',
            ],
        ]));

        $this->assertTrue(true);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function makeMedia(array $attrs): CustomMedia
    {
        $media = new CustomMedia();
        $media->forceFill(array_merge([
            'id' => 1,
            'model_type' => ArchiveFile::class,
            'model_id' => 'model-1',
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'collection_name' => 'upload',
            'name' => 'file',
            'file_name' => 'file.bin',
            'mime_type' => 'application/octet-stream',
            'disk' => 'local',
            'conversions_disk' => 'local',
            'size' => 10,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
        ], $attrs));

        return $media;
    }
}
