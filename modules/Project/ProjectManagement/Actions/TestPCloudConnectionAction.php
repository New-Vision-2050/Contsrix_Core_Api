<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Actions;

use Illuminate\Support\Str;
use Modules\Project\ProjectManagement\Exceptions\PCloudConfigurationException;
use Modules\Shared\PCloud\Services\PCloudClient;

final class TestPCloudConnectionAction
{
    private const TEST_FOLDER = '__server-pcloud-test__';

    public function __construct(
        private readonly PCloudClient $client,
    ) {}

    /**
     * Upload a small, isolated file to confirm this server can reach pCloud.
     *
     * @return array{path: string, filename: string, folder_id: int, file_id: int|null, skipped: bool}
     */
    public function execute(): array
    {
        if (! $this->client->isConfigured()) {
            throw new PCloudConfigurationException('pCloud integration is disabled or not configured.');
        }

        $timestamp = now();
        $path = implode('/', [
            trim((string) config('pcloud.root_folder', 'Constrix Archive'), '/'),
            self::TEST_FOLDER,
            $timestamp->format('Y-m-d'),
        ]);
        $filename = 'pcloud-test-'.$timestamp->format('Ymd-His').'-'.Str::uuid().'.txt';
        $folderId = $this->client->ensureFolderPath($path);
        $upload = $this->client->uploadFile(
            $folderId,
            $filename,
            "pCloud server connection test\nTimestamp: {$timestamp->toIso8601String()}\n",
            'text/plain',
        );

        return [
            'path' => $path,
            'filename' => $filename,
            'folder_id' => $folderId,
            'file_id' => isset($upload['metadata'][0]['fileid'])
                ? (int) $upload['metadata'][0]['fileid']
                : (isset($upload['fileids'][0]) ? (int) $upload['fileids'][0] : null),
            'skipped' => (bool) ($upload['skipped'] ?? false),
        ];
    }
}
