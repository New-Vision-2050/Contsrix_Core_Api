<?php

declare(strict_types=1);

namespace Modules\Shared\PCloud\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Media\Models\CustomMedia;
use Modules\Shared\PCloud\Services\PCloudArchiveSyncService;
use Throwable;

class SyncMediaToPCloudJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly string $mediaId,
        public readonly ?string $companyId = null,
    ) {
    }

    public function handle(PCloudArchiveSyncService $syncService): void
    {
        if ($this->companyId) {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            tenancy()->initialize($this->companyId);
        }

        try {
            $media = CustomMedia::query()->find($this->mediaId);
            if (!$media) {
                Log::warning('pCloud sync job: media not found', [
                    'media_id' => $this->mediaId,
                ]);

                return;
            }

            $syncService->syncMedia($media);
        } finally {
            if ($this->companyId && tenancy()->initialized) {
                tenancy()->end();
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('pCloud sync job failed permanently', [
            'media_id' => $this->mediaId,
            'company_id' => $this->companyId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
