<?php

declare(strict_types=1);

namespace Modules\Reports\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Reports\DTO\CreateReportDTO;
use Modules\Reports\DTO\ReportWizardConfigDTO;
use Modules\Reports\Enums\ReportStatus;
use Modules\Reports\Jobs\GenerateReportJob;
use Modules\Reports\Models\Report;
use Modules\Reports\Repositories\ReportRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ReportCRUDService
{
    public function __construct(
        private ReportRepository    $repository,
        private ReportPeriodResolver $periodResolver,
        private ReportLookupService  $lookupService,
    ) {
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page:    $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Report
    {
        return $this->repository->getReport($id);
    }

    public function create(CreateReportDTO $dto): Report
    {
        $config = $dto->config;
        $period = $this->periodResolver->resolve($config->step1);

        $name = $dto->name ?: $this->buildDefaultName($config);

        $report = $this->repository->create([
            'id'                => Uuid::uuid4()->toString(),
            'company_id'        => tenant('id'),
            'created_by'        => Auth::id(),
            'template_id'       => $dto->templateId,
            'name'              => $name,
            'report_types'      => $config->step1->reportTypeIds,
            'period_type'       => $config->step1->periodType,
            'year'              => $config->step1->year,
            'month'             => $config->step1->month,
            'week'              => $config->step1->week,
            'quarter'           => $config->step1->quarter,
            'period_start'      => $period['start']->toDateString(),
            'period_end'        => $period['end']->toDateString(),
            'export_format'     => $config->step1->exportFormat,
            'language'          => $config->step1->reportLanguage,
            'paper_size'        => $config->step1->paperSize,
            'print_orientation' => $config->step1->printOrientation,
            'config'            => $config->toArray(),
            'status'            => ReportStatus::PENDING,
        ]);

        GenerateReportJob::dispatch($report->id, tenant('id'));

        return $report->fresh();
    }

    public function regenerate(UuidInterface $id): Report
    {
        $report = $this->repository->getReport($id);

        $this->repository->markProcessing($id);

        GenerateReportJob::dispatch($report->id, $report->company_id);

        return $report->fresh();
    }

    public function delete(UuidInterface $id): bool
    {
        $report = $this->repository->getReport($id);

        if ($report->file_path && $report->file_disk) {
            $disk = Storage::disk($report->file_disk);
            if ($disk->exists($report->file_path)) {
                $disk->delete($report->file_path);
            }
        }

        return $this->repository->deleteById($id);
    }

    /**
     * Stream the generated file to the caller as an HTTP download.
     */
    public function download(UuidInterface $id): Response
    {
        $report = $this->repository->getReport($id);

        if (!$report->isReady() || !$report->file_path || !$report->file_disk) {
            abort(409, __('Report is not ready for download yet.'));
        }

        $disk = Storage::disk($report->file_disk);
        if (!$disk->exists($report->file_path)) {
            abort(404, __('Report file is missing.'));
        }

        return response($disk->get($report->file_path), 200, [
            'Content-Type'        => $disk->mimeType($report->file_path) ?: 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . basename($report->file_path) . '"',
            'Content-Length'      => (string) ($report->file_size ?: $disk->size($report->file_path)),
        ]);
    }

    /**
     * Build a default translatable name from the selected report types + period.
     */
    private function buildDefaultName(ReportWizardConfigDTO $config): array
    {
        $typeLabelsByLang = ['ar' => [], 'en' => []];
        $catalog          = $this->lookupService->reportTypes();

        foreach ($catalog as $entry) {
            if (in_array($entry['id'], $config->step1->reportTypeIds, true)) {
                $typeLabelsByLang['ar'][] = $entry['label']['ar'];
                $typeLabelsByLang['en'][] = $entry['label']['en'];
            }
        }

        $suffix = $config->step1->year;
        if ($config->step1->month !== null) {
            $suffix = sprintf('%02d/%d', $config->step1->month, $config->step1->year);
        } elseif ($config->step1->quarter !== null) {
            $suffix = 'Q' . $config->step1->quarter . ' ' . $config->step1->year;
        } elseif ($config->step1->week !== null) {
            $suffix = 'W' . $config->step1->week . ' ' . $config->step1->year;
        }

        return [
            'ar' => implode(' + ', $typeLabelsByLang['ar']) . ' - ' . $suffix,
            'en' => implode(' + ', $typeLabelsByLang['en']) . ' - ' . $suffix,
        ];
    }
}
