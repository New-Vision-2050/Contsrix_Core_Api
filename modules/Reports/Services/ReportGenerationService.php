<?php

declare(strict_types=1);

namespace Modules\Reports\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;
use Modules\Reports\DTO\ReportWizardConfigDTO;
use Modules\Reports\Enums\ReportEnums;
use Modules\Reports\Exports\ReportCsvExport;
use Modules\Reports\Exports\ReportExcelExport;
use Modules\Reports\Models\Report;
use Modules\Reports\Repositories\ReportRepository;
use Ramsey\Uuid\Uuid;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
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

            $media = match ($config->step1->exportFormat) {
                ReportEnums::FORMAT_EXCEL => $this->renderExcel($report, $config, $employees, $sections),
                ReportEnums::FORMAT_CSV   => $this->renderCsv($report, $config, $employees, $sections),
                ReportEnums::FORMAT_PDF   => $this->renderPdf($report, $config, $employees, $sections),
                default                   => throw new \RuntimeException('Unsupported export format: ' . $config->step1->exportFormat),
            };

            $this->repository->markReady(
                id:       $id,
                filePath: $media->uuid,
                fileDisk: 'media',
                fileSize: $media->size,
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
     */
    private function renderExcel(Report $report, ReportWizardConfigDTO $config, $employees, array $sections): Media
    {
        $tmpPath = 'reports-tmp/' . $report->id . '.xlsx';
        $export  = new ReportExcelExport($report, $config, $employees, $sections, $this->lookupService);

        Excel::store($export, $tmpPath, 'local');
        $contents = (string) Storage::disk('local')->get($tmpPath);
        Storage::disk('local')->delete($tmpPath);

        return $this->storeAsMedia($report, $contents, 'xlsx');
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
     */
    private function renderCsv(Report $report, ReportWizardConfigDTO $config, $employees, array $sections): Media
    {
        $tmpPath = 'reports-tmp/' . $report->id . '.csv';
        $export  = new ReportCsvExport($report, $config, $employees, $sections, $this->lookupService);

        Excel::store($export, $tmpPath, 'local', \Maatwebsite\Excel\Excel::CSV);
        $contents = (string) Storage::disk('local')->get($tmpPath);
        Storage::disk('local')->delete($tmpPath);

        return $this->storeAsMedia($report, $contents, 'csv');
    }

    /**
     * Real PDF rendered with mPDF (`mpdf/mpdf`).
     *
     * mPDF is used instead of DomPDF specifically because it has *native*
     * Arabic support: contextual letter shaping (initial/medial/final/isolated
     * forms via `autoArabic = true`) and full bi-directional (BiDi) text flow.
     * DomPDF prints Arabic code points as standalone, left-to-right glyphs,
     * which is why words came out disconnected and reversed.
     *
     * Honours `paperSize` / `printOrientation` and the report language from
     * the wizard. For Arabic reports the document direction is forced to RTL
     * at the page level so tables/headers/footers also flow right-to-left.
     *
     * @param \Illuminate\Support\Collection $employees
     * @param array<string, array<string,mixed>> $sections
     */
    private function renderPdf(Report $report, ReportWizardConfigDTO $config, $employees, array $sections): Media
    {
        $isArabic    = $config->step1->reportLanguage === ReportEnums::LANGUAGE_AR;
        $orientation = strtoupper($config->step1->printOrientation) === 'LANDSCAPE' ? 'L' : 'P';
        $paper       = $config->step1->paperSize ?: 'A4';

        $tempDir = storage_path('app/mpdf-temp');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode'             => 'utf-8',
            'format'           => $paper,
            'orientation'      => $orientation,
            'tempDir'          => $tempDir,
            'default_font'     => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'autoArabic'       => true,
            'useSubstitutions' => true,
            'margin_left'      => 12,
            'margin_right'     => 12,
            'margin_top'       => 14,
            'margin_bottom'    => 14,
        ]);

        if ($isArabic) {
            $mpdf->SetDirectionality('rtl');
        }

        $mpdf->SetTitle((string) ($report->getTranslation('name', $config->step1->reportLanguage) ?? 'Report'));
        $mpdf->SetCreator('Constrix Reports');

        $avatarCache = $this->buildAvatarCache($employees);

        $html = view('reports::pdf.report', [
            'report'      => $report,
            'config'      => $config,
            'employees'   => $employees,
            'sections'    => $sections,
            'lookups'     => $this->lookupService,
            'avatarCache' => $avatarCache,
        ])->render();

        ini_set('pcre.backtrack_limit', '50000000');
        $mpdf->WriteHTML($html);

        return $this->storeAsMedia(
            $report,
            $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN),
            'pdf'
        );
    }

    /**
     * Resolve each employee's avatar to a base64 data URI once, keyed by global_id.
     * Avoids mPDF making one HTTP request per <img> tag (×days per employee).
     */
    private function buildAvatarCache($employees): array
    {
        $cache = [];
        foreach ($employees as $emp) {
            $media = $emp->getFirstMedia('upload_user');
            if (!$media) {
                continue;
            }
            try {
                $path = $media->getPath();
                if (file_exists($path) && filesize($path) > 0) {
                    $cache[(string) $emp->global_id] =
                        'data:' . ($media->mime_type ?: 'image/jpeg') . ';base64,'
                        . base64_encode((string) file_get_contents($path));
                }
            } catch (\Throwable) {
                // leave absent — blade will fall back to placeholder
            }
        }
        return $cache;
    }

    private function storeAsMedia(Report $report, string $contents, string $extension): Media
    {
        $bucket = config('filesystems.disks.s3_public.bucket');
        $disk   = (is_string($bucket) && $bucket !== '') ? 's3_public' : 'public';

        return $report
            ->addMediaFromString($contents)
            ->usingFileName($report->id . '.' . $extension)
            ->usingName($report->id)
            ->toMediaCollection('report_file', $disk);
    }
}
