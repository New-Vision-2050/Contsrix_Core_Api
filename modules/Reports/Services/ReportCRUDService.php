<?php

declare(strict_types=1);

namespace Modules\Reports\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Reports\DTO\CreateReportDTO;
use Modules\Reports\DTO\ReportWizardConfigDTO;
use Modules\Reports\Enums\ReportEnums;
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

    public function list(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        return $this->repository->paginated(
            page:    $page,
            perPage: $perPage,
            filters: $filters,
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
            'serial_number'     => $this->repository->generateSerialNumber(),
            'company_id'        => tenant('id'),
            'created_by'        => Auth::id(),
            'template_id'       => $dto->templateId,
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

        // Set translations using HasTranslations trait
        if (is_array($name)) {
            foreach ($name as $locale => $value) {
                $report->setTranslation('name', $locale, $value);
            }
            $report->save();
        }

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

        // Legacy reports stored directly on a Storage disk (not Media Library)
        if ($report->file_path && $report->file_disk && $report->file_disk !== 'media') {
            $disk = Storage::disk($report->file_disk);
            if ($disk->exists($report->file_path)) {
                $disk->delete($report->file_path);
            }
        }
        // Media Library files are removed automatically when the Eloquent model is deleted

        return $this->repository->deleteById($id);
    }

    /**
     * Stream the generated file to the caller as an HTTP download.
     *
     * Content-Type is derived from `export_format` (NOT from the storage
     * driver's MIME guess), so the browser always sees the right type even
     * if a stale file with a wrong extension lives on disk. The filename is
     * built from the report's translated name + correct extension and is
     * sent both as ASCII (`filename=`) and RFC 5987 UTF-8 (`filename*=`)
     * so Arabic names render correctly in the browser's Save dialog.
     */
    public function download(UuidInterface $id): Response
    {
        $report = $this->repository->getReport($id);

        if (!$report->isReady()) {
            abort(409, __('Report is not ready for download yet.'));
        }

        [$mime, $extension] = $this->mimeAndExtensionFor($report->export_format);
        $downloadName       = $this->buildDownloadFilename($report, $extension);
        $asciiName          = $this->asciiFallback($downloadName);
        $rfc5987Name        = rawurlencode($downloadName);

        $headers = [
            'Content-Type'                  => $mime,
            'Content-Disposition'       => sprintf(
                'attachment; filename="%s"; filename*=UTF-8\'\'%s',
                $asciiName,
                $rfc5987Name,
            ),
            'X-Content-Type-Options'        => 'nosniff',
            'Access-Control-Expose-Headers' => 'Content-Disposition, Content-Length, Content-Type',
        ];

        // New: file stored via Spatie Media Library (DigitalOcean Spaces / S3)
        if ($report->file_disk === 'media') {
            $media = $report->getFirstMedia('report_file');
            if (!$media) {
                abort(404, __('Report file is missing.'));
            }
            $contents = Storage::disk($media->disk)->get($media->getPathRelativeToRoot());
            $headers['Content-Length'] = (string) ($report->file_size ?: strlen((string) $contents));

            return response($contents, 200, $headers);
        }

        // Backward compat: legacy reports written directly to a Storage disk
        if (!$report->file_path || !$report->file_disk) {
            abort(409, __('Report is not ready for download yet.'));
        }

        $disk = Storage::disk($report->file_disk);
        if (!$disk->exists($report->file_path)) {
            abort(404, __('Report file is missing.'));
        }

        $headers['Content-Length'] = (string) ($report->file_size ?: $disk->size($report->file_path));

        return response($disk->get($report->file_path), 200, $headers);
    }

    /**
     * @return array{0:string,1:string} [mime, extension]
     */
    private function mimeAndExtensionFor(string $format): array
    {
        return match ($format) {
            ReportEnums::FORMAT_PDF   => ['application/pdf', 'pdf'],
            ReportEnums::FORMAT_EXCEL => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'xlsx'],
            ReportEnums::FORMAT_CSV   => ['text/csv; charset=UTF-8', 'csv'],
            default                   => ['application/octet-stream', 'bin'],
        };
    }

    /**
     * Build a filename like "attendance-absence-q2-2026.pdf" using the
     * report's translated name (preferring the report's own language).
     */
    private function buildDownloadFilename(Report $report, string $extension): string
    {
        $lang = $report->language ?: ReportEnums::LANGUAGE_EN;

        $raw = is_array($report->name)
            ? ($report->name[$lang] ?? ($report->name[ReportEnums::LANGUAGE_EN] ?? reset($report->name)))
            : (string) $report->name;

        $raw = trim((string) $raw);
        if ($raw === '') {
            $raw = 'report-' . $report->id;
        }

        // Slug keeps unicode letters (so Arabic stays readable) but strips
        // path separators / control chars that break Content-Disposition.
        $clean = preg_replace('/[\\/\\\\:*?"<>|\\r\\n\\t]+/u', '-', $raw);
        $clean = preg_replace('/\\s+/u', ' ', (string) $clean);
        $clean = trim((string) $clean, " -");

        return ($clean === '' ? ('report-' . $report->id) : $clean) . '.' . $extension;
    }

    /**
     * Strict ASCII fallback for the legacy `filename=` parameter. Uses
     * Str::slug so the browser always has a printable name even when the
     * report title is fully non-Latin.
     */
    private function asciiFallback(string $name): string
    {
        $dot       = strrpos($name, '.');
        $base      = $dot === false ? $name : substr($name, 0, $dot);
        $extension = $dot === false ? ''    : substr($name, $dot + 1);

        $slug = Str::slug($base, '-');
        if ($slug === '') {
            $slug = 'report';
        }

        return $extension === '' ? $slug : ($slug . '.' . $extension);
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
