<?php

declare(strict_types=1);

namespace Modules\Reports\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Reports\DTO\ReportWizardConfigDTO;
use Modules\Reports\Enums\ReportEnums;
use Modules\Reports\Exports\ReportCsvExport;
use Modules\Reports\Exports\ReportExcelExport;
use Modules\Reports\Models\Report;
use Modules\Reports\Repositories\ReportRepository;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * Orchestrates the end-to-end generation of a persisted `Report`: resolves
 * the employee set + per-type data slices, renders the output file, and
 * updates the report row with the final status.
 *
 * This service is the single extension point for file generation — replace
 * `renderPdf` / `renderCsv` / `renderExcel` with your real renderers as the
 * reporting stack matures.
 */
class ReportGenerationService
{
    public const DISK = 'public';

    public function __construct(
        private ReportRepository           $repository,
        private ReportEmployeeQueryService $employeeQueryService,
        private ReportLookupService        $lookupService,
        private ReportDataExtractionService $dataExtractionService,
    ) {
    }

    public function generate(Report $report): void
    {
        $id = Uuid::fromString($report->id);

        try {
            $this->repository->markProcessing($id);

            $config    = ReportWizardConfigDTO::fromArray($report->config ?? []);
            $employees = $this->employeeQueryService->query($config->step2)->get();

            // Per-section data payload keyed by report-type slug (attendance, leaves, ...).
            $sections = $this->dataExtractionService->extract($report, $config, $employees);

            $artifact = match ($config->step1->exportFormat) {
                ReportEnums::FORMAT_EXCEL => $this->renderExcel($report, $config, $employees, $sections),
                ReportEnums::FORMAT_CSV   => $this->renderCsv($report, $config, $employees, $sections),
                ReportEnums::FORMAT_PDF   => $this->renderPdf($report, $config, $employees, $sections),
                default                   => throw new \RuntimeException('Unsupported export format: ' . $config->step1->exportFormat),
            };

            $this->repository->markReady(
                id:       $id,
                filePath: $artifact['path'],
                fileDisk: $artifact['disk'],
                fileSize: $artifact['size'] ?? null,
            );
        } catch (Throwable $e) {
            Log::error('[Reports] generation failed', [
                'report_id' => $report->id,
                'error'     => $e->getMessage(),
            ]);
            $this->repository->markFailed($id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * @param \Illuminate\Support\Collection $employees
     * @param array<string, array<string,mixed>> $sections
     * @return array{path:string,disk:string,size:?int}
     */
    private function renderExcel(Report $report, ReportWizardConfigDTO $config, $employees, array $sections): array
    {
        $path = $this->buildPath($report, 'xlsx');
        $export = new ReportExcelExport($report, $config, $employees, $sections, $this->lookupService);

        Excel::store($export, $path, self::DISK);

        return [
            'path' => $path,
            'disk' => self::DISK,
            'size' => Storage::disk(self::DISK)->exists($path) ? Storage::disk(self::DISK)->size($path) : null,
        ];
    }

    /**
     * Single-sheet CSV containing the actual employee/metric rows.
     *
     * The previous implementation reused the multi-sheet `ReportExcelExport`,
     * which made Maatwebsite/Excel write only the first sheet (the metadata
     * cover) into the CSV — that's why downloads looked like a "CSV with HTML"
     * instead of real data.
     *
     * @param \Illuminate\Support\Collection $employees
     * @param array<string, array<string,mixed>> $sections
     * @return array{path:string,disk:string,size:?int}
     */
    private function renderCsv(Report $report, ReportWizardConfigDTO $config, $employees, array $sections): array
    {
        $path   = $this->buildPath($report, 'csv');
        $export = new ReportCsvExport($report, $config, $employees, $sections, $this->lookupService);

        Excel::store($export, $path, self::DISK, \Maatwebsite\Excel\Excel::CSV);

        return [
            'path' => $path,
            'disk' => self::DISK,
            'size' => Storage::disk(self::DISK)->exists($path) ? Storage::disk(self::DISK)->size($path) : null,
        ];
    }

    /**
     * Real PDF rendered with DomPDF (`barryvdh/laravel-dompdf`).
     *
     * Honours `paperSize` / `printOrientation` from the wizard. The blade
     * view (`reports::pdf.report`) already ships with RTL/LTR styling, so
     * Arabic reports render right-to-left automatically.
     *
     * NOTE: For Arabic glyph shaping you may want to register a unicode font
     * (e.g. Cairo/Amiri) via `config/dompdf.php` — DejaVu Sans (the default)
     * supports Arabic but does not perform contextual shaping.
     *
     * @param \Illuminate\Support\Collection $employees
     * @param array<string, array<string,mixed>> $sections
     * @return array{path:string,disk:string,size:?int}
     */
    private function renderPdf(Report $report, ReportWizardConfigDTO $config, $employees, array $sections): array
    {
        $path = $this->buildPath($report, 'pdf');

        $pdf = Pdf::loadView('reports::pdf.report', [
            'report'    => $report,
            'config'    => $config,
            'employees' => $employees,
            'sections'  => $sections,
            'lookups'   => $this->lookupService,
        ])
            ->setPaper(
                strtolower($config->step1->paperSize ?: 'a4'),
                strtolower($config->step1->printOrientation ?: 'portrait'),
            )
            ->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);

        Storage::disk(self::DISK)->put($path, $pdf->output());

        return [
            'path' => $path,
            'disk' => self::DISK,
            'size' => Storage::disk(self::DISK)->size($path),
        ];
    }

    private function buildPath(Report $report, string $extension): string
    {
        return sprintf(
            'reports/%s/%s.%s',
            $report->company_id,
            $report->id,
            $extension,
        );
    }
}
