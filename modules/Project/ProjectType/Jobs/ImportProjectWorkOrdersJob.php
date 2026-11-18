<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Project\ProjectType\Imports\WorkOrderExcelHeaderValidator;
use Modules\Project\ProjectType\Models\ProjectWorkOrderExcelImport;
use Modules\Project\ProjectType\Services\ProjectWorkOrderExcelImportService;
use Modules\Project\ProjectType\Support\WorkOrderExcelImportLogger;
use Modules\User\Models\User;

final class ImportProjectWorkOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public $tries = 1;

    public function __construct(
        public readonly string $importId,
        public readonly string $filePath,
        public readonly string $projectId,
        public readonly string $companyId,
        public readonly ?string $userId = null,
    ) {}

    public function handle(ProjectWorkOrderExcelImportService $service): void
    {
        $disk = Storage::disk('local');
        $import = ProjectWorkOrderExcelImport::query()->find($this->importId);

        WorkOrderExcelImportLogger::info('job_started', [
            'import_id' => $this->importId,
            'project_id' => $this->projectId,
            'company_id' => $this->companyId,
            'user_id' => $this->userId,
            'stored_path' => $this->filePath,
            'status' => 'started',
        ]);

        if ($import === null) {
            WorkOrderExcelImportLogger::error('import_record_missing', [
                'import_id' => $this->importId,
                'project_id' => $this->projectId,
                'company_id' => $this->companyId,
                'status' => 'failed',
            ]);

            return;
        }

        $import->forceFill([
            'status' => ProjectWorkOrderExcelImport::STATUS_PROCESSING,
            'started_at' => now(),
        ])->save();

        if (! $disk->exists($this->filePath)) {
            $reason = 'Import file not found on local disk when job started: '.$this->filePath;
            $import->forceFill([
                'status' => ProjectWorkOrderExcelImport::STATUS_FAILED,
                'failure_reason' => $reason,
                'finished_at' => now(),
            ])->save();

            WorkOrderExcelImportLogger::error('file_missing_at_job_start', [
                'import_id' => $this->importId,
                'project_id' => $this->projectId,
                'company_id' => $this->companyId,
                'stored_path' => $this->filePath,
                'status' => 'failed',
                'error_message' => $reason,
            ]);

            return;
        }

        WorkOrderExcelImportLogger::info('file_opened', [
            'import_id' => $this->importId,
            'project_id' => $this->projectId,
            'company_id' => $this->companyId,
            'stored_path' => $this->filePath,
            'absolute_path' => $disk->path($this->filePath),
            'status' => 'ok',
        ]);

        try {
            if (tenancy()->initialized) {
                tenancy()->end();
            }

            tenancy()->initialize($this->companyId);

            $actorName = null;
            if ($this->userId) {
                $actorName = User::query()
                    ->withoutGlobalScopes()
                    ->where('id', $this->userId)
                    ->value('name');
            }

            $rows = Excel::toArray([], $disk->path($this->filePath))[0] ?? [];

            WorkOrderExcelImportLogger::info('excel_parsed', [
                'import_id' => $this->importId,
                'project_id' => $this->projectId,
                'company_id' => $this->companyId,
                'sheet_row_count' => count($rows),
                'status' => 'ok',
            ]);

            if ($rows === []) {
                $import->forceFill([
                    'status' => ProjectWorkOrderExcelImport::STATUS_COMPLETED,
                    'total_rows' => 0,
                    'processed_rows' => 0,
                    'updated_rows' => 0,
                    'skipped_rows' => 0,
                    'row_errors' => [],
                    'row_warnings' => [],
                    'finished_at' => now(),
                ])->save();

                WorkOrderExcelImportLogger::info('job_completed_empty_sheet', [
                    'import_id' => $this->importId,
                    'project_id' => $this->projectId,
                    'company_id' => $this->companyId,
                    'status' => 'completed',
                ]);

                return;
            }

            (new WorkOrderExcelHeaderValidator)->validate($rows[0] ?? []);

            WorkOrderExcelImportLogger::info('header_validated_in_job', [
                'import_id' => $this->importId,
                'project_id' => $this->projectId,
                'company_id' => $this->companyId,
                'status' => 'ok',
            ]);

            $result = $service->importRows(
                $rows,
                $this->projectId,
                $this->companyId,
                $this->importId,
                $this->userId,
                $actorName !== null ? (string) $actorName : null,
            );

            $status = ($result['skipped'] > 0 || count($result['warnings']) > 0)
                ? ProjectWorkOrderExcelImport::STATUS_COMPLETED_WITH_ERRORS
                : ProjectWorkOrderExcelImport::STATUS_COMPLETED;

            $import->forceFill([
                'status' => $status,
                'total_rows' => $result['total_data_rows'],
                'processed_rows' => $result['processed'],
                'updated_rows' => $result['updated'],
                'skipped_rows' => $result['skipped'],
                'row_errors' => $result['errors'],
                'row_warnings' => $result['warnings'],
                'finished_at' => now(),
            ])->save();

            WorkOrderExcelImportLogger::info('job_completed', [
                'import_id' => $this->importId,
                'project_id' => $this->projectId,
                'company_id' => $this->companyId,
                'user_id' => $this->userId,
                'status' => $status,
                'total_rows' => $result['total_data_rows'],
                'processed_rows' => $result['processed'],
                'updated_rows' => $result['updated'],
                'skipped_rows' => $result['skipped'],
                'error_count' => count($result['errors']),
                'warning_count' => count($result['warnings']),
            ]);
        } catch (\Throwable $e) {
            $import->forceFill([
                'status' => ProjectWorkOrderExcelImport::STATUS_FAILED,
                'failure_reason' => $e->getMessage(),
                'finished_at' => now(),
            ])->save();

            WorkOrderExcelImportLogger::error('job_failed', [
                'import_id' => $this->importId,
                'project_id' => $this->projectId,
                'company_id' => $this->companyId,
                'user_id' => $this->userId,
                'stored_path' => $this->filePath,
                'status' => 'failed',
            ], $e);

            throw $e;
        } finally {
            $deleted = false;
            try {
                $deleted = $disk->delete($this->filePath);
            } catch (\Throwable $cleanupError) {
                WorkOrderExcelImportLogger::error('temp_file_cleanup_failed', [
                    'import_id' => $this->importId,
                    'project_id' => $this->projectId,
                    'company_id' => $this->companyId,
                    'stored_path' => $this->filePath,
                    'status' => 'cleanup_failed',
                ], $cleanupError);
            }

            WorkOrderExcelImportLogger::info('temp_file_cleanup', [
                'import_id' => $this->importId,
                'project_id' => $this->projectId,
                'company_id' => $this->companyId,
                'stored_path' => $this->filePath,
                'status' => $deleted ? 'deleted' : 'not_deleted',
            ]);
        }
    }
}
