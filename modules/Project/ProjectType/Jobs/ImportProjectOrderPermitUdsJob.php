<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Project\ProjectType\Imports\ProjectOrderPermitUdsImport;
use Modules\Project\ProjectType\Imports\UdsExcelHeaderValidator;
use Modules\Project\ProjectType\Models\OrderPermit;
use Modules\Project\ProjectType\Models\ProjectOrderPermitUds;
use Modules\Project\ProjectType\Services\ProjectOrderPermitService;

class ImportProjectOrderPermitUdsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 1;

    private const BATCH_SIZE = 500;

    public function __construct(
        private readonly string $filePath,
        private readonly string $projectId,
        private readonly string $companyId,
    ) {
    }

    public function handle(ProjectOrderPermitService $service): void
    {
        $disk = Storage::disk('public');

        if (!$disk->exists($this->filePath)) {
            Log::error('UDS import file not found: ' . $this->filePath);

            return;
        }

        $fullPath = $disk->path($this->filePath);

        try {
            // 1) Read Excel — before any DB mutation
            $rows = Excel::toArray([], $fullPath)[0] ?? [];

            if (empty($rows)) {
                Log::info('UDS import finished with empty sheet; old UDS data kept', [
                    'project_id' => $this->projectId,
                ]);

                return;
            }

            // 1b) Official Header must match before any delete/insert/update
            (new UdsExcelHeaderValidator())->validate($rows[0] ?? []);

            // 2) Validate / map (skip header; one Excel row → one UDS row)
            $mapper = new ProjectOrderPermitUdsImport();
            $now = now()->toDateTimeString();
            $records = [];

            // Classify Excel rows for storage only (code = contractor, type = consultant).
            // Work-order sync still uses Team Lead logic against each order's OrderPermit.
            $contractorCodes = OrderPermit::query()
                ->whereNotNull('code')
                ->pluck('code')
                ->mapWithKeys(fn ($code) => [(string) $code => true])
                ->all();

            $consultantTypes = OrderPermit::query()
                ->whereNotNull('type')
                ->pluck('type')
                ->mapWithKeys(fn ($type) => [(string) $type => true])
                ->all();

            foreach ($rows as $index => $row) {
                if ($index === 0 || $mapper->isHeaderRow($row)) {
                    continue;
                }

                $typeCode = trim((string) ($row[35] ?? ''));
                $isContractorRow = $typeCode !== '' && isset($contractorCodes[$typeCode]);
                $isConsultantRow = $typeCode !== '' && isset($consultantTypes[$typeCode]);

                $mapped = $mapper->mapRow(
                    $row,
                    $this->projectId,
                    $this->companyId,
                    (string) Str::uuid(),
                    $now,
                    $isContractorRow,
                    $isConsultantRow,
                );

                if ($mapped === null) {
                    continue;
                }

                $records[] = $mapped;
            }

            if (empty($records)) {
                Log::warning('UDS import produced no valid rows; old UDS data kept', [
                    'project_id' => $this->projectId,
                    'sheet_rows' => count($rows),
                ]);

                return;
            }

            // 3) Delete → Insert → Sync work orders (atomic)
            DB::transaction(function () use ($records, $service) {
                ProjectOrderPermitUds::withoutGlobalScopes()
                    ->where('project_id', $this->projectId)
                    ->delete();

                foreach (array_chunk($records, self::BATCH_SIZE) as $chunk) {
                    ProjectOrderPermitUds::insert($chunk);
                }

                $service->updateWorkOrdersFromUds($this->projectId);
            });

            Log::info('UDS import completed', [
                'project_id' => $this->projectId,
                'imported_rows' => count($records),
            ]);
        } catch (\Throwable $e) {
            Log::error('UDS import failed: ' . $e->getMessage(), [
                'project_id' => $this->projectId,
                'file' => $this->filePath,
            ]);

            throw $e;
        } finally {
            // Always remove temp upload (success or failure)
            $disk->delete($this->filePath);
        }
    }
}
