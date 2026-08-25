<?php

declare(strict_types=1);

namespace Modules\Reports\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportCRUDService
{
    public function __construct(
        private ReportRepository            $repository,
        private ReportPeriodResolver        $periodResolver,
        private ReportLookupService         $lookupService,
        private ReportGenerationService     $generationService,
    ) {
    }

    public function list(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        return $this->repository->paginated(
            conditions: [],
            page:       $page,
            perPage:    $perPage,
            orderBy:    'created_at',
            sortBy:     'desc',
            filters:    $filters,
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

    /**
     * Create a report for a specific employee and generate it synchronously
     * (no queue). Defaults to PDF with all report types and all columns.
     */
    public function createEmployeeReport(
        string $userId,
        string $dateFrom,
        string $dateTo,
        ?array $name = null,
        string $reportLanguage = ReportEnums::LANGUAGE_AR,
        string $paperSize = ReportEnums::PAPER_A4,
        string $printOrientation = ReportEnums::ORIENTATION_PORTRAIT,
    ): Report {
        $dateFromCarbon = \Carbon\Carbon::parse($dateFrom);
        $dateToCarbon   = \Carbon\Carbon::parse($dateTo);

        // Build default wizard config with all report types and all columns
        $config = new ReportWizardConfigDTO(
            step1: new \Modules\Reports\DTO\ReportWizardStep1DTO(
                reportTypeIds:    ReportEnums::reportTypes(),
                periodType:       ReportEnums::PERIOD_RANGE,
                year:             (int) $dateFromCarbon->format('Y'),
                month:            null,
                week:             null,
                quarter:          null,
                exportFormat:     ReportEnums::FORMAT_PDF,
                reportLanguage:   $reportLanguage,
                paperSize:        $paperSize,
                printOrientation: $printOrientation,
                dateFrom:         $dateFrom,
                dateTo:           $dateTo,
            ),
            step2: new \Modules\Reports\DTO\ReportWizardStep2DTO(
                employeeScope:   ReportEnums::EMPLOYEE_SCOPE_SELECT_EMPLOYEES,
                employeeUserIds: [$userId],
                branchId:        null,
                managementId:    null,
                department:      null,
                jobTitle:        null,
                contractTypeIds: [],
                nationality:     null,
                gender:          null,
            ),
            step3: new \Modules\Reports\DTO\ReportWizardStep3DTO(
                attendanceDataTypeIds:       ReportEnums::attendanceDetailColumns(),
                displayMode:                 ReportEnums::DISPLAY_MODE_EMPLOYEE_PER_PAGE,
                attendancePattern:           ReportEnums::ATT_PATTERN_ALL,
                attendanceRateMin:           ReportEnums::ATT_RATE_NO_FILTER,
                delayLimitMinutes:           ReportEnums::DELAY_NO_FILTER,
                minOvertime:                 ReportEnums::OT_NO_FILTER,
                includeEntryExitTime:        true,
                includeShiftName:            true,
                includeAttendanceNotes:      true,
                calculateTotalWorkHours:     true,
                showPreviousMonthComparison: false,
            ),
            step4: new \Modules\Reports\DTO\ReportWizardStep4DTO(
                salaryComponentIds:          ReportEnums::salaryComponents(),
                deductionIds:                ReportEnums::salaryDeductions(),
                disbursementStatus:          ReportEnums::DISBURSEMENT_ALL,
                netSalaryOnly:               false,
                compareWithPreviousMonth:    false,
                employeeDetailsSeparatePage:   false,
                addTotalSummaryEnd:            true,
            ),
            step5: new \Modules\Reports\DTO\ReportWizardStep5DTO(
                mainSortBy:          ReportEnums::SORT_BY_EMPLOYEE_NAME_ALPHA,
                sortDirection:       ReportEnums::SORT_DIR_ASC,
                groupBy:             ReportEnums::GROUP_BY_NONE,
                employeesPerPage:    '25',
                visualElementIds:    [],
                autoEmail:           false,
                copyToManager:       false,
                monthlyScheduling:   false,
                companyHeaderFooter: true,
                digitalSignature:    false,
                recipientEmails:     '',
            ),
        );

        $reportName = $name ?: $this->buildDefaultName($config);

        $report = $this->repository->create([
            'id'                => Uuid::uuid4()->toString(),
            'serial_number'     => $this->repository->generateSerialNumber(),
            'company_id'        => tenant('id'),
            'created_by'        => Auth::id(),
            'template_id'       => null,
            'report_types'      => $config->step1->reportTypeIds,
            'period_type'       => $config->step1->periodType,
            'year'              => $config->step1->year,
            'month'             => $config->step1->month,
            'week'              => $config->step1->week,
            'quarter'           => $config->step1->quarter,
            'period_start'      => $dateFromCarbon->toDateString(),
            'period_end'        => $dateToCarbon->toDateString(),
            'export_format'     => $config->step1->exportFormat,
            'language'          => $config->step1->reportLanguage,
            'paper_size'        => $config->step1->paperSize,
            'print_orientation' => $config->step1->printOrientation,
            'config'            => $config->toArray(),
            'status'            => ReportStatus::PENDING,
        ]);

        // Set translations using HasTranslations trait
        if (is_array($reportName)) {
            foreach ($reportName as $locale => $value) {
                $report->setTranslation('name', $locale, $value);
            }
            $report->save();
        }

        // Generate synchronously — no queue
        $this->generationService->generate($report);

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
     * Resolve a short-lived object-storage URL for SPA downloads.
     *
     * @return array{download_url:string,file_name:string,mime:string,file_size:int|null,expires_in:int}
     */
    public function resolveDownloadUrl(UuidInterface $id): array
    {
        $report = $this->repository->getReport($id);

        if (!$report->isReady()) {
            Log::warning('[Reports] download: report not ready', [
                'report_id' => $report->id,
                'status'    => $report->status,
            ]);
            abort(409, __('Report is not ready for download yet.'));
        }

        [$mime, $extension] = $this->mimeAndExtensionFor($report->export_format);
        $downloadName       = $this->buildDownloadFilename($report, $extension);
        $asciiName          = $this->asciiFallback($downloadName);
        $disposition        = sprintf(
            'attachment; filename="%s"; filename*=UTF-8\'\'%s',
            $asciiName,
            rawurlencode($downloadName),
        );

        $media = $report->getFirstMedia('report_file');
        if ($media === null && $report->file_disk === 'media') {
            abort(404, __('Report file is missing.'));
        }

        if ($media !== null) {
            $url = $this->buildMediaDownloadUrl($media, $mime, $disposition, $report->id);
            if ($url === null) {
                abort(404, __('Report file is missing.'));
            }

            return [
                'download_url' => $url,
                'file_name'    => $downloadName,
                'mime'         => $mime,
                'file_size'    => $report->file_size,
                'expires_in'   => 1800,
            ];
        }

        // Legacy disk files have no public URL — client must use ?stream=1
        abort(409, __('Direct download URL is unavailable; use stream=1.'));
    }

    /**
     * Download the generated report file (stream or storage redirect).
     *
     * Prefer resolveDownloadUrl() for SPA clients. This path is for
     * ?stream=1 / legacy consumers that need the raw bytes from the API.
     */
    public function download(UuidInterface $id): StreamedResponse
    {
        $report = $this->repository->getReport($id);

        if (!$report->isReady()) {
            Log::warning('[Reports] download: report not ready', [
                'report_id' => $report->id,
                'status'    => $report->status,
            ]);
            abort(409, __('Report is not ready for download yet.'));
        }

        [$mime, $extension] = $this->mimeAndExtensionFor($report->export_format);
        $downloadName       = $this->buildDownloadFilename($report, $extension);
        $asciiName          = $this->asciiFallback($downloadName);
        $rfc5987Name        = rawurlencode($downloadName);
        $disposition        = sprintf(
            'attachment; filename="%s"; filename*=UTF-8\'\'%s',
            $asciiName,
            $rfc5987Name,
        );

        $headers = [
            'Content-Type'                  => $mime,
            'Content-Disposition'           => $disposition,
            'X-Content-Type-Options'        => 'nosniff',
            'Access-Control-Expose-Headers' => 'Content-Disposition, Content-Length, Content-Type',
        ];

        $media = $report->getFirstMedia('report_file');
        if ($report->file_disk === 'media' || $media !== null) {
            if ($media === null) {
                Log::warning('[Reports] download: media record missing', [
                    'report_id' => $report->id,
                    'status'    => $report->status,
                ]);
                abort(404, __('Report file is missing.'));
            }

            if ($report->file_size) {
                $headers['Content-Length'] = (string) $report->file_size;
            }

            return $this->streamFromDisk(
                (string) $media->disk,
                $media->getPathRelativeToRoot(),
                $headers,
                $report->id,
            );
        }

        if (!$report->file_path || !$report->file_disk) {
            abort(409, __('Report is not ready for download yet.'));
        }

        $disk = Storage::disk($report->file_disk);
        if (!$disk->exists($report->file_path)) {
            abort(404, __('Report file is missing.'));
        }

        $headers['Content-Length'] = (string) ($report->file_size ?: $disk->size($report->file_path));

        return $this->streamFromDisk($report->file_disk, $report->file_path, $headers, $report->id);
    }

    /**
     * Build a signed/public object-storage URL (no HTTP redirect).
     */
    private function buildMediaDownloadUrl(
        Media $media,
        string $mime,
        string $disposition,
        string $reportId,
    ): ?string {
        $diskName = (string) $media->disk;
        $path     = $media->getPathRelativeToRoot();

        try {
            $url = Storage::disk($diskName)->temporaryUrl(
                $path,
                now()->addMinutes(30),
                [
                    'ResponseContentType'        => $mime,
                    'ResponseContentDisposition' => $disposition,
                ],
            );
        } catch (\Throwable $e) {
            Log::info('[Reports] download: temporaryUrl unavailable, trying public URL', [
                'report_id' => $reportId,
                'disk'      => $diskName,
                'path'      => $path,
                'error'     => $e->getMessage(),
            ]);

            try {
                $url = $media->getFullUrl();
            } catch (\Throwable $inner) {
                Log::warning('[Reports] download: media URL resolution failed', [
                    'report_id' => $reportId,
                    'error'     => $inner->getMessage(),
                ]);

                return null;
            }
        }

        return is_string($url) && $url !== '' ? $url : null;
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function streamFromDisk(string $diskName, string $path, array $headers, string $reportId): StreamedResponse
    {
        return response()->stream(function () use ($diskName, $path, $reportId): void {
            try {
                $stream = Storage::disk($diskName)->readStream($path);
            } catch (\Throwable $e) {
                Log::error('[Reports] download: failed to open storage stream', [
                    'report_id' => $reportId,
                    'disk'      => $diskName,
                    'path'      => $path,
                    'error'     => $e->getMessage(),
                ]);

                return;
            }

            if ($stream === false) {
                Log::error('[Reports] download: storage stream unavailable', [
                    'report_id' => $reportId,
                    'disk'      => $diskName,
                    'path'      => $path,
                ]);

                return;
            }

            try {
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, 200, $headers);
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
