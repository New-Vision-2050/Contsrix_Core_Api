<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Project\ProjectType\Imports\WorkOrderExcelHeaderValidator;
use Modules\Project\ProjectType\Services\ProjectWorkOrderExcelImportService;

final class ImportProjectWorkOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public $tries = 1;

    public function __construct(
        public readonly string $filePath,
        public readonly string $projectId,
        public readonly string $companyId,
    ) {}

    public function handle(ProjectWorkOrderExcelImportService $service): void
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($this->filePath)) {
            Log::error('Project Work Orders import file not found: '.$this->filePath);

            return;
        }

        try {
            $rows = Excel::toArray([], $disk->path($this->filePath))[0] ?? [];

            if ($rows === []) {
                Log::info('Project Work Orders import finished with empty sheet', [
                    'project_id' => $this->projectId,
                ]);

                return;
            }

            (new WorkOrderExcelHeaderValidator)->validate($rows[0] ?? []);

            $result = $service->importRows($rows, $this->projectId, $this->companyId);

            Log::info('Project Work Orders import completed', [
                'project_id' => $this->projectId,
                'updated_rows' => $result['updated'],
                'skipped_rows' => $result['skipped'],
                'row_errors' => $result['errors'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Project Work Orders import failed: '.$e->getMessage(), [
                'project_id' => $this->projectId,
                'file' => $this->filePath,
            ]);

            throw $e;
        } finally {
            $disk->delete($this->filePath);
        }
    }
}
