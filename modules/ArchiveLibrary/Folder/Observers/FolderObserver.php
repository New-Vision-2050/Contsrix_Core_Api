<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Observers;

use Illuminate\Support\Facades\Log;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\Shared\PCloud\Services\PCloudArchiveSyncService;

/**
 * Ensure every Archive Library folder is mirrored on pCloud.
 */
class FolderObserver
{
    public function created(Folder $folder): void
    {
        $this->ensurePCloudFolder($folder);
    }

    public function updated(Folder $folder): void
    {
        if ($folder->wasChanged(['name', 'parent_id', 'company_id', 'project_id'])) {
            $this->ensurePCloudFolder($folder);
        }
    }

    private function ensurePCloudFolder(Folder $folder): void
    {
        try {
            app(PCloudArchiveSyncService::class)->ensureArchiveFolder($folder);
        } catch (\Throwable $e) {
            Log::error('Failed to ensure pCloud folder for archive folder', [
                'folder_id' => $folder->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
