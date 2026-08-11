<?php

namespace Modules\Project\ProjectType\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Project\ProjectType\Exceptions\SafetyException;
use Modules\Project\ProjectType\Models\SafetyWeeklyReport;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class SafetyWeeklyReportService
{
    /**
     * Exact page size from original template MediaBox (1111.pdf / weekly-safety-report.pdf).
     *
     * MediaBox: [0, 0, 842.88, 595.92]  (PDF points, 1 pt = 1/72 inch)
     * CropBox:  same as MediaBox on all 18 pages
     * Orientation: landscape (width > height)
     *
     * These are PAGE dimensions — not chart image pixel sizes.
     */
    private const PAGE_WIDTH_PT = 842.88;

    private const PAGE_HEIGHT_PT = 595.92;

    /** @see PAGE_WIDTH_PT — converted for mPDF (mm) */
    private const PAGE_WIDTH_MM = self::PAGE_WIDTH_PT * 25.4 / 72;

    /** @see PAGE_HEIGHT_PT — converted for mPDF (mm) */
    private const PAGE_HEIGHT_MM = self::PAGE_HEIGHT_PT * 25.4 / 72;

    private const STATIC_INTRO_PAGES = 7;

    private const STATIC_OUTRO_START = 17;

    private const STATIC_OUTRO_END = 18;

    /** Template page used as the contractor 2×2 layout source of truth. */
    private const CONTRACTOR_TEMPLATE_PAGE = 11;

    private const CONTRACTORS_PER_PAGE = 4;

    public function __construct(
        private SafetyAnalyticsService $analytics,
        private SafetyWeeklyChartRenderer $charts,
    ) {}

    /**
     * Generate PDF, persist report metadata + file, return the saved record.
     * PDF generation content/design is unchanged — only persistence is added.
     */
    public function createAndStore(string $projectId, string $fromDate, string $toDate): SafetyWeeklyReport
    {
        $report = SafetyWeeklyReport::query()->create([
            'project_id' => $projectId,
            'created_by' => auth()->id(),
            'serial_number' => $this->generateSerialNumber(),
            'name' => $this->buildReportName($fromDate, $toDate),
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'status' => SafetyWeeklyReport::STATUS_PENDING,
        ]);

        try {
            $binary = $this->generate($projectId, $fromDate, $toDate);
            $media = $this->storeAsMedia($report, $binary);

            $report->forceFill([
                'status' => SafetyWeeklyReport::STATUS_READY,
                'file_path' => (string) $media->uuid,
                'file_disk' => 'media',
                'file_size' => strlen($binary),
                'generated_at' => now(),
                'error_message' => null,
            ])->save();
        } catch (Throwable $e) {
            $report->forceFill([
                'status' => SafetyWeeklyReport::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ])->save();

            throw $e;
        }

        return $report->fresh();
    }

    /**
     * Create + persist, then stream the PDF (keeps previous download UX).
     */
    public function download(string $projectId, string $fromDate, string $toDate): Response
    {
        $report = $this->createAndStore($projectId, $fromDate, $toDate);

        return $this->downloadStored($projectId, (string) $report->id);
    }

    /**
     * @return Collection<int, SafetyWeeklyReport>
     */
    public function listByProject(string $projectId): Collection
    {
        return SafetyWeeklyReport::query()
            ->where('project_id', $projectId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function findForProject(string $projectId, string $reportId): SafetyWeeklyReport
    {
        $report = SafetyWeeklyReport::query()
            ->where('project_id', $projectId)
            ->where('id', $reportId)
            ->first();

        if (! $report) {
            throw SafetyException::weeklyReportNotFound();
        }

        return $report;
    }

    public function downloadStored(string $projectId, string $reportId): Response
    {
        $report = $this->findForProject($projectId, $reportId);

        if (! $report->isReady()) {
            throw SafetyException::weeklyReportNotReady();
        }

        $contents = $this->readStoredPdf($report);
        $filename = sprintf(
            'weekly-safety-report-%s-%s.pdf',
            optional($report->from_date)?->format('Y-m-d') ?? 'from',
            optional($report->to_date)?->format('Y-m-d') ?? 'to'
        );

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) ($report->file_size ?: strlen($contents)),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function storeAsMedia(SafetyWeeklyReport $report, string $contents): Media
    {
        $bucket = config('filesystems.disks.s3_public.bucket');
        $disk = (is_string($bucket) && $bucket !== '') ? 's3_public' : 'public';

        return $report
            ->addMediaFromString($contents)
            ->usingFileName($report->id.'.pdf')
            ->usingName($report->name)
            ->withCustomProperties([
                'file_path' => 'safety/weekly-reports/'.$report->project_id,
            ])
            ->toMediaCollection(SafetyWeeklyReport::MEDIA_COLLECTION, $disk);
    }

    private function readStoredPdf(SafetyWeeklyReport $report): string
    {
        if ($report->file_disk === 'media') {
            $media = $report->getFirstMedia(SafetyWeeklyReport::MEDIA_COLLECTION);
            if (! $media) {
                throw SafetyException::weeklyReportFileMissing();
            }

            try {
                return (string) Storage::disk($media->disk)->get($media->getPathRelativeToRoot());
            } catch (Throwable $e) {
                Log::error('[SafetyWeeklyReport] failed to read stored PDF', [
                    'report_id' => $report->id,
                    'media_id' => $media->id,
                    'disk' => $media->disk,
                    'error' => $e->getMessage(),
                ]);
                throw SafetyException::weeklyReportFileMissing();
            }
        }

        throw SafetyException::weeklyReportFileMissing();
    }

    private function buildReportName(string $fromDate, string $toDate): string
    {
        return sprintf('تقرير مهمام السلامة - %s إلى %s', $fromDate, $toDate);
    }

    /**
     * Format: REP-2026-0001 (sequence resets each calendar year).
     */
    private function generateSerialNumber(): string
    {
        $year = now()->format('Y');
        $prefix = 'REP-'.$year.'-';

        $lastSerial = SafetyWeeklyReport::query()
            ->withoutGlobalScopes()
            ->where('serial_number', 'like', $prefix.'%')
            ->orderByDesc('serial_number')
            ->value('serial_number');

        $next = $lastSerial ? ((int) substr((string) $lastSerial, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function generate(string $projectId, string $fromDate, string $toDate): string
    {
        $template = $this->resolveTemplatePath();
        if ($template === null) {
            throw SafetyException::weeklyReportTemplateMissing();
        }

        $compliance = $this->analytics->contractorCompliance($projectId, $fromDate, $toDate);
        $frequencies = $this->analytics->globalViolationFrequencies($fromDate, $toDate);
        $topViolations = $this->analytics->topViolations(5, $fromDate, $toDate);
        $contractors = $this->analytics->projectContractorsForReport($projectId);

        $contractorPages = $contractors
            ->chunk(self::CONTRACTORS_PER_PAGE)
            ->map(function (Collection $chunk) use ($projectId, $fromDate, $toDate) {
                return $chunk->map(function (array $contractor) use ($projectId, $fromDate, $toDate) {
                    $top = $this->analytics->contractorTopViolations(
                        $projectId,
                        $contractor['id'],
                        $fromDate,
                        $toDate,
                        5
                    );

                    return [
                        'id' => $contractor['id'],
                        'name' => $contractor['name'] ?? '',
                        'violations' => $top,
                    ];
                })->values();
            })
            ->values();

        $mpdf = $this->makeMpdf();
        $mpdf->SetSourceFile($template);

        // Pages 1–7: static template
        for ($page = 1; $page <= self::STATIC_INTRO_PAGES; $page++) {
            $this->addTemplatePage($mpdf, $page);
        }

        $this->renderCompliancePage($mpdf, $compliance);
        $this->renderFrequencyPage($mpdf, $frequencies, $fromDate, $toDate);
        $this->renderTopViolationsPage($mpdf, $topViolations, $fromDate, $toDate);

        if ($contractorPages->isEmpty()) {
            $this->renderContractorPage($mpdf, collect());
        } else {
            foreach ($contractorPages as $pageContractors) {
                $this->renderContractorPage($mpdf, $pageContractors);
            }
        }

        // Last two static pages (template 17–18)
        for ($page = self::STATIC_OUTRO_START; $page <= self::STATIC_OUTRO_END; $page++) {
            $this->addTemplatePage($mpdf, $page);
        }

        $binary = $mpdf->Output('', Destination::STRING_RETURN);
        $this->logGeneratedPageGeometry($binary, $mpdf);

        return $binary;
    }

    private function resolveTemplatePath(): ?string
    {
        $candidates = [
            base_path('modules/Project/ProjectType/Resources/templates/weekly-safety-report.pdf'),
            storage_path('app/templates/weekly-safety-report.pdf'),
        ];

        foreach ($candidates as $absolute) {
            if (is_file($absolute)) {
                return $absolute;
            }
        }

        return null;
    }

    /**
     * Build mPDF as A4 Landscape matching the original template MediaBox.
     *
     * mPDF quirk: with orientation=L it SWAPS format[0]/format[1].
     * So we pass the base paper as [height, width] (210.23, 297.35),
     * and orientation L produces the final landscape canvas:
     *   Width  = 297.35 mm = 842.88 pt
     *   Height = 210.23 mm = 595.92 pt
     */
    private function makeMpdf(): Mpdf
    {
        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $fontDirs = (new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'];
        $fontDirs[] = 'C:/Windows/Fonts';

        $fontData = (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'];
        $defaultFont = 'dejavusans';

        if (is_file('C:/Windows/Fonts/arial.ttf')) {
            $fontData['arial'] = [
                'R' => 'arial.ttf',
                'B' => is_file('C:/Windows/Fonts/arialbd.ttf') ? 'arialbd.ttf' : 'arial.ttf',
                'useOTL' => 0xFF,
                'useKashida' => 75,
            ];
            $defaultFont = 'arial';
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            // Base paper dims in mPDF "portrait-input" order; L swaps to landscape.
            // Final page = PAGE_WIDTH_MM × PAGE_HEIGHT_MM (297.35 × 210.23).
            'format' => [self::PAGE_HEIGHT_MM, self::PAGE_WIDTH_MM],
            'orientation' => 'L',
            'tempDir' => $tempDir,
            'fontDir' => $fontDirs,
            'fontdata' => $fontData,
            'default_font' => $defaultFont,
            'default_font_size' => 11,
            'dpi' => 120,
            'img_dpi' => 120,
            'autoScriptToLang' => true,
            'autoLangToFont' => false,
            'autoArabic' => true,
            'useSubstitutions' => false,
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle('تقرير السلامة الأسبوعي');
        $mpdf->SetCreator('Constrix Safety');
        $mpdf->shrink_tables_to_fit = 0;
        ini_set('pcre.backtrack_limit', '50000000');

        return $mpdf;
    }

    /**
     * Add a page using the original template on an A4 Landscape canvas.
     * Does not rotate content after the fact — page is created landscape from the start.
     */
    private function addTemplatePage(Mpdf $mpdf, int $pageNumber): void
    {
        $tplId = $mpdf->ImportPage($pageNumber);

        // Same mPDF L-swap rule as makeMpdf: pass [height, width] + orientation L
        // so the resulting MediaBox is WIDTH × HEIGHT (842.88 × 595.92 pt).
        $mpdf->AddPageByArray([
            'orientation' => 'L',
            'sheet-size' => [self::PAGE_HEIGHT_MM, self::PAGE_WIDTH_MM],
        ]);

        // Place template on the landscape canvas (width > height). Never rotate.
        $mpdf->UseTemplate(
            $tplId,
            0,
            0,
            self::PAGE_WIDTH_MM,
            self::PAGE_HEIGHT_MM
        );
    }

    /**
     * Verify generated PDF MediaBox and log geometry (no rotation applied).
     */
    private function logGeneratedPageGeometry(string $pdfBinary, Mpdf $mpdf): void
    {
        $widthPt = null;
        $heightPt = null;

        if (preg_match('/\/MediaBox\s*\[\s*([0-9.\-]+)\s+([0-9.\-]+)\s+([0-9.\-]+)\s+([0-9.\-]+)\s*\]/', $pdfBinary, $m)) {
            $widthPt = (float) $m[3] - (float) $m[1];
            $heightPt = (float) $m[4] - (float) $m[2];
        }

        if ($widthPt === null || $heightPt === null) {
            // Fallback to mPDF runtime page box (mm → pt).
            $widthPt = (float) $mpdf->w * 72 / 25.4;
            $heightPt = (float) $mpdf->h * 72 / 25.4;
        }

        $orientation = $widthPt > $heightPt ? 'Landscape' : ($widthPt < $heightPt ? 'Portrait' : 'Square');

        Log::info('Weekly safety PDF page geometry', [
            'Generated Page Width' => $widthPt,
            'Generated Page Height' => $heightPt,
            'Orientation' => $orientation,
            'Expected Width Pt' => self::PAGE_WIDTH_PT,
            'Expected Height Pt' => self::PAGE_HEIGHT_PT,
            'mPDF w mm' => $mpdf->w,
            'mPDF h mm' => $mpdf->h,
            'mPDF CurOrientation' => $mpdf->CurOrientation ?? null,
        ]);
    }

    /**
     * Page 8 — compliance bar chart over template chrome.
     *
     * @param  Collection<int, array{contractor_id: string, contractor_name: string|null, percentage: float, completed_tasks: int}>  $compliance
     */
    private function renderCompliancePage(Mpdf $mpdf, Collection $compliance): void
    {
        $this->addTemplatePage($mpdf, 8);
        $this->whiteout($mpdf, 18, 32, 261, 145);

        $items = $compliance->map(fn (array $row) => [
            'name' => (string) ($row['contractor_name'] ?? ''),
            'percentage' => (float) $row['percentage'],
        ])->values()->all();

        if ($items === []) {
            $mpdf->WriteHTML($this->centeredMessageHtml('لا توجد مهام سلامة مكتملة في الفترة المحددة'));

            return;
        }

        $chart = $this->charts->renderComplianceBarChart($items);
        $mpdf->WriteHTML('<div style="text-align:center;padding-top:34mm;">'
            .'<img src="'.$chart.'" style="width:250mm;height:auto;" />'
            .'</div>');
    }

    /**
     * Page 9 — global violation frequency pie.
     *
     * @param  Collection<int, array{code: mixed, description: mixed, percentage: float}>  $frequencies
     */
    private function renderFrequencyPage(Mpdf $mpdf, Collection $frequencies, string $fromDate, string $toDate): void
    {
        $this->addTemplatePage($mpdf, 9);
        // Cover original title + chart so we can redraw with dynamic date range.
        $this->whiteout($mpdf, 10, 8, 277, 170);

        $items = $frequencies->map(fn (array $row) => [
            'label' => (string) ($row['description'] ?? ''),
            'code' => (string) ($row['code'] ?? ''),
            'percentage' => (float) $row['percentage'],
        ])->values()->all();

        $period = $this->formatPeriod($fromDate, $toDate);
        $title = 'نسب تكرار جميع الأخطاء لجميع المقاولين مع جميع الاستشاريين';
        $chart = $items === []
            ? null
            : $this->charts->renderPieChart($items, 640, true)['image'];

        $mpdf->WriteHTML($this->analyticsPageHtml($title, $period, $chart, $items === [] ? 'لا توجد مخالفات في الفترة المحددة' : null));
    }

    /**
     * Page 10 — top 5 global violations.
     *
     * @param  Collection<int, array{code: mixed, description: mixed, percentage: float}>  $topViolations
     */
    private function renderTopViolationsPage(Mpdf $mpdf, Collection $topViolations, string $fromDate, string $toDate): void
    {
        $this->addTemplatePage($mpdf, 10);
        $this->whiteout($mpdf, 10, 8, 277, 170);

        $items = $topViolations->map(fn (array $row) => [
            'label' => (string) ($row['description'] ?? ''),
            'code' => (string) ($row['code'] ?? ''),
            'percentage' => (float) $row['percentage'],
        ])->values()->all();

        $period = $this->formatPeriod($fromDate, $toDate);
        $title = 'نسب تكرار أكبر خمس مخالفات لجميع المقاولين مع جميع الاستشاريين';
        $chart = $items === []
            ? null
            : $this->charts->renderPieChart($items, 620, true)['image'];

        $mpdf->WriteHTML($this->analyticsPageHtml($title, $period, $chart, $items === [] ? 'لا توجد مخالفات في الفترة المحددة' : null));
    }

    /**
     * Contractor pages: clone template page 11 (exact layout/grid/chrome),
     * clear sample quadrant data, then fill only occupied slots.
     * Empty slots stay blank inside the original template structure.
     *
     * @param  Collection<int, array{id: string, name: string, violations: Collection}>  $contractors
     */
    private function renderContractorPage(Mpdf $mpdf, Collection $contractors): void
    {
        $this->addTemplatePage($mpdf, self::CONTRACTOR_TEMPLATE_PAGE);

        // Quadrant content boxes inside the original 2×2 grid (mm, from template layout).
        // Index order matches template visual slots: 0=top-right, 1=top-left, 2=bottom-right, 3=bottom-left (RTL page).
        $quadrants = $this->contractorQuadrantBoxes();

        // Always clear sample data from all 4 quadrants so leftover template contractors never leak.
        foreach ($quadrants as $box) {
            $this->whiteout($mpdf, $box['x'], $box['y'], $box['w'], $box['h']);
        }

        $slots = $contractors->values()->all();
        foreach ($slots as $index => $contractor) {
            if ($index >= self::CONTRACTORS_PER_PAGE || $contractor === null) {
                break;
            }
            $this->writeContractorQuadrant($mpdf, $quadrants[$index], $contractor);
        }
        // Remaining quadrants stay empty (already whitened) — same as original empty slots.
    }

    /**
     * Content regions for the 4 contractor quadrants on template page 11.
     * Coordinates are in mm relative to the template page size (MediaBox-derived).
     *
     * @return list<array{x: float, y: float, w: float, h: float}>
     */
    private function contractorQuadrantBoxes(): array
    {
        // Derived from template page 11 grid: outer content frame with 2×2 split.
        // Page size ≈ PAGE_WIDTH_MM × PAGE_HEIGHT_MM.
        $left = 16.0;
        $top = 30.0;
        $width = self::PAGE_WIDTH_MM - 32.0;
        $height = 148.0;
        $halfW = $width / 2;
        $halfH = $height / 2;
        $pad = 1.5;

        // Visual order on RTL template: right column first, then left.
        return [
            // 0 — top-right
            ['x' => $left + $halfW + $pad, 'y' => $top + $pad, 'w' => $halfW - 2 * $pad, 'h' => $halfH - 2 * $pad],
            // 1 — top-left
            ['x' => $left + $pad, 'y' => $top + $pad, 'w' => $halfW - 2 * $pad, 'h' => $halfH - 2 * $pad],
            // 2 — bottom-right
            ['x' => $left + $halfW + $pad, 'y' => $top + $halfH + $pad, 'w' => $halfW - 2 * $pad, 'h' => $halfH - 2 * $pad],
            // 3 — bottom-left
            ['x' => $left + $pad, 'y' => $top + $halfH + $pad, 'w' => $halfW - 2 * $pad, 'h' => $halfH - 2 * $pad],
        ];
    }

    /**
     * @param  array{x: float, y: float, w: float, h: float}  $box
     * @param  array{id: string, name: string, violations: Collection}  $contractor
     */
    private function writeContractorQuadrant(Mpdf $mpdf, array $box, array $contractor): void
    {
        $name = htmlspecialchars((string) $contractor['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $items = $contractor['violations']->map(fn (array $row) => [
            'label' => (string) ($row['description'] ?? ''),
            'code' => (string) ($row['code'] ?? ''),
            'percentage' => (float) $row['percentage'],
        ])->values()->all();

        $body = '<div style="color:#1a3c7e;font-weight:bold;font-size:11pt;text-align:center;direction:rtl;">'.$name.'</div>';
        if ($items === []) {
            $body .= '<div style="color:#888;font-size:9pt;text-align:center;margin-top:18mm;direction:rtl;">لا توجد مخالفات</div>';
        } else {
            $chart = $this->charts->renderContractorPie($items, 480);
            $body .= '<div style="text-align:center;"><img src="'.$chart.'" style="width:'.max(40, $box['w'] - 4).'mm;height:auto;" /></div>';
        }

        $mpdf->WriteFixedPosHTML(
            '<div style="width:'.$box['w'].'mm;">'.$body.'</div>',
            $box['x'],
            $box['y'],
            $box['w'],
            $box['h'],
            'auto'
        );
    }

    private function whiteout(Mpdf $mpdf, float $x, float $y, float $w, float $h): void
    {
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($x, $y, $w, $h, 'F');
    }

    private function formatPeriod(string $fromDate, string $toDate): string
    {
        return $fromDate.' – '.$toDate;
    }

    private function analyticsPageHtml(string $title, string $period, ?string $chartDataUri, ?string $emptyMessage): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safePeriod = htmlspecialchars($period, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $body = $chartDataUri
            ? '<img src="'.$chartDataUri.'" style="width:230mm;height:auto;" />'
            : '<div style="color:#888;font-size:14pt;margin-top:40mm;">'
                .htmlspecialchars((string) $emptyMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                .'</div>';

        return <<<HTML
<div style="text-align:center;padding-top:8mm;direction:rtl;">
  <div style="color:#1a3c7e;font-size:16pt;font-weight:bold;">{$safeTitle}</div>
  <div style="color:#1a3c7e;font-size:13pt;margin-top:2mm;">{$safePeriod}</div>
  <div style="margin-top:4mm;">{$body}</div>
</div>
HTML;
    }

    private function centeredMessageHtml(string $message): string
    {
        $safe = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<div style="text-align:center;padding-top:70mm;color:#888;font-size:14pt;direction:rtl;">'.$safe.'</div>';
    }
}
