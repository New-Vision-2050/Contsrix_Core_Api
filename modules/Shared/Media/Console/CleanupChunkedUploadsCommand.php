<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Console;

use Illuminate\Console\Command;
use Modules\Shared\Media\Services\ChunkedUploadService;

class CleanupChunkedUploadsCommand extends Command
{
    protected $signature = 'chunked-uploads:cleanup {--hours=6 : Delete temp upload directories older than this many hours}';

    protected $description = 'Remove stale/orphaned chunked (resumable) upload temp files';

    public function handle(ChunkedUploadService $service): int
    {
        $hours = (int) $this->option('hours');
        $removed = $service->cleanupExpired($hours);

        $this->info("Removed {$removed} stale chunked upload director" . ($removed === 1 ? 'y' : 'ies') . " older than {$hours}h.");

        return self::SUCCESS;
    }
}
