<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TruncateApiRequestLogsCommand extends Command
{
    protected $signature = 'api-request-logs:truncate
                            {--dry-run : Show the row count that would be deleted without truncating}';

    protected $description = 'Truncate the api_request_logs table (runs on the central database).';

    public function handle(): int
    {
        $count = DB::table('api_request_logs')->count();

        if ($this->option('dry-run')) {
            $this->info("[DRY RUN] api_request_logs currently holds {$count} rows — nothing deleted.");

            return self::SUCCESS;
        }

        try {
            DB::table('api_request_logs')->truncate();
        } catch (\Throwable $e) {
            Log::error('api_request_logs truncate failed', ['error' => $e->getMessage()]);
            $this->error("Truncate failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        Log::info('api_request_logs truncated', ['deleted_rows' => $count]);
        $this->info("Truncated api_request_logs — deleted {$count} rows.");

        return self::SUCCESS;
    }
}
