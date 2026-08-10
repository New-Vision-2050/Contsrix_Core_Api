<?php

namespace Modules\Project\ProjectType\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectType\Exceptions\SafetyException;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectType\Models\SafetyRecord;
use Modules\Project\ProjectType\Models\Violation;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class SafetyViolationReportService
{
    private const VIOLATIONS_PER_PDF = 2;

    private const EVIDENCE_PER_GALLERY_PAGE = 4;

    private const FOUND_STATUS = 'violation_found';

    public function download(string $projectId, string $safetyRecordId): Response|BinaryFileResponse
    {
        $record = $this->loadRecord($projectId, $safetyRecordId);
        $foundViolations = $this->foundViolations($record);

        if ($foundViolations->isEmpty()) {
            throw SafetyException::noFoundViolations();
        }

        $header = $this->resolveHeader($record);
        $chunks = $foundViolations->chunk(self::VIOLATIONS_PER_PDF)->values();
        $pdfBinaries = [];

        foreach ($chunks as $index => $chunk) {
            $chunkViolations = $chunk->values();
            $payload = $this->buildChunkPayload($record, $header, $chunkViolations);
            $pdfBinaries[] = [
                'filename' => sprintf(
                    'safety-violation-report-%s-%d.pdf',
                    $record->id,
                    $index + 1
                ),
                'content' => $this->renderPdf($payload),
            ];
        }

        if (count($pdfBinaries) === 1) {
            $pdf = $pdfBinaries[0];

            return response($pdf['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$pdf['filename'].'"',
            ]);
        }

        return $this->zipResponse($record->id, $pdfBinaries);
    }

    private function loadRecord(string $projectId, string $safetyRecordId): SafetyRecord
    {
        $record = SafetyRecord::query()
            ->where('project_id', $projectId)
            ->where('id', $safetyRecordId)
            ->with([
                'violations',
                'contractor',
                'project.manager',
                'assignedUser.professionalData',
                'company',
                'media',
                'morphable',
            ])
            ->first();

        if (! $record) {
            throw SafetyException::notFound();
        }

        if ($record->morphable instanceof ProjectNotification) {
            $record->morphable->loadMissing(['contractorRepresentative']);
        }

        if ($record->morphable instanceof ProjectOrderPermit) {
            $record->morphable->loadMissing(['projectDistrict']);
        }

        return $record;
    }

    /**
     * @return Collection<int, Violation>
     */
    private function foundViolations(SafetyRecord $record): Collection
    {
        return $record->violations
            ->filter(fn (Violation $violation) => (string) ($violation->pivot->status ?? '') === self::FOUND_STATUS)
            ->sortBy(fn (Violation $violation) => (string) $violation->code)
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveHeader(SafetyRecord $record): array
    {
        $morphable = $record->morphable;
        $workOrder = $morphable?->name
            ?? $morphable?->notification_number
            ?? null;

        $location = null;
        $contractorSafetyRep = null;

        if ($morphable instanceof ProjectNotification) {
            $location = $morphable->district
                ?: $morphable->full_address
                ?: $morphable->repair_point;
            $contractorSafetyRep = $morphable->contractorRepresentative?->name;
        } elseif ($morphable instanceof ProjectOrderPermit) {
            $location = $morphable->projectDistrict?->name;
        }

        $timeSource = $record->inspection_time ?: $record->time;
        if (is_string($timeSource) && strlen($timeSource) >= 5) {
            $timeSource = substr($timeSource, 0, 5);
        }

        $formattedTime = null;
        if (is_string($timeSource) && preg_match('/^\d{2}:\d{2}/', $timeSource) === 1) {
            $formattedTime = date('h:i A', strtotime($timeSource));
        }

        $inspectionDate = $record->inspection_date
            ? $record->inspection_date->format('d/m/Y')
            : ($record->date?->format('d/m/Y'));

        $assignedUser = $record->assignedUser;

        return [
            'contractor_name' => $this->clampText($record->contractor?->name, 42),
            'inspection_date' => $inspectionDate,
            'inspection_time' => $formattedTime,
            'project_name' => $this->clampText($record->project?->name, 58),
            'work_order' => $this->clampText($workOrder, 28),
            'order_type' => $record->order_type,
            'project_manager' => $this->clampText($record->project?->manager?->name, 28),
            'violation_location' => $this->clampText($location, 36),
            'contractor_safety_rep' => $this->clampText($contractorSafetyRep, 32),
            'preparer_name' => $this->clampText(
                $assignedUser?->name ?? $record->consultant_engineer,
                24
            ),
            'preparer_job_code' => $this->clampText($assignedUser?->professionalData?->job_code, 16),
            'planning_manager_name' => '',
            'company_name' => $record->company?->name,
            'company_logo' => $this->resolveReportLogoDataUri(),
        ];
    }

    /**
     * @param  Collection<int, Violation>  $chunkViolations
     * @param  array<string, mixed>  $header
     * @return array<string, mixed>
     */
    private function buildChunkPayload(SafetyRecord $record, array $header, Collection $chunkViolations): array
    {
        $violations = $chunkViolations->map(function (Violation $violation) {
            $weight = abs((float) ($violation->pivot->weight ?? $violation->default_weight ?? 0));

            return [
                'id' => (string) $violation->id,
                'code' => $this->clampText($violation->code, 18),
                'description' => $this->clampText($violation->description, 78),
                'category' => $this->clampText($violation->category, 1),
                'penalty' => $weight,
                'penalty_display' => $this->formatPenalty($weight),
                'repetition' => '1',
                'actions' => array_map(
                    fn (string $action): string => $this->clampText($action, 42),
                    $violation->actions()
                ),
                'pivot_action' => $violation->pivot->action ?? null,
            ];
        })->values()->all();

        // Always pad to exactly 2 slots so page-1 geometry never changes.
        while (count($violations) < self::VIOLATIONS_PER_PDF) {
            $violations[] = $this->emptyViolationSlot();
        }
        $violations = array_slice($violations, 0, self::VIOLATIONS_PER_PDF);

        $filledViolations = array_values(array_filter(
            $violations,
            static fn (array $violation): bool => ($violation['id'] ?? '') !== ''
        ));

        $totalPenalty = collect($filledViolations)->sum('penalty');
        $actions = $this->uniqueActions($filledViolations);
        $evidence = $this->evidenceForViolations($record, $chunkViolations);
        $description = $this->clampText(
            collect($filledViolations)
                ->pluck('description')
                ->filter()
                ->implode(' / '),
            140
        );

        // Page 1: one fixed side image slot. Remaining images use fixed gallery pages.
        $sideEvidence = array_slice($evidence, 0, 1);
        $galleryEvidence = array_slice($evidence, 1);

        return [
            'header' => $header,
            'violations' => $violations,
            'total_penalty' => $totalPenalty,
            'total_penalty_display' => $this->formatPenalty($totalPenalty),
            'actions' => $actions,
            'evidence' => $evidence,
            'description' => $description,
            'primary_evidence' => $sideEvidence[0] ?? null,
            'side_evidence' => $sideEvidence,
            'below_evidence' => [],
            'gallery_evidence' => $galleryEvidence,
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     code: string,
     *     description: string,
     *     category: string,
     *     penalty: float,
     *     penalty_display: string,
     *     repetition: string,
     *     actions: list<string>,
     *     pivot_action: null
     * }
     */
    private function emptyViolationSlot(): array
    {
        return [
            'id' => '',
            'code' => '',
            'description' => '',
            'category' => '',
            'penalty' => 0.0,
            'penalty_display' => '',
            'repetition' => '1',
            'actions' => [],
            'pivot_action' => null,
        ];
    }

    private function formatPenalty(float $value): string
    {
        if ((float) (int) $value === $value) {
            return (string) (int) $value;
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function clampText(mixed $value, int $maxChars): string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '' || $maxChars < 1) {
            return '';
        }

        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }

        return mb_substr($text, 0, max(1, $maxChars - 1)).'…';
    }

    /**
     * @param  list<array{actions: list<string>}>  $violations
     * @return list<string>
     */
    private function uniqueActions(array $violations): array
    {
        $actions = [];

        foreach ($violations as $violation) {
            foreach ($violation['actions'] as $action) {
                if (! in_array($action, $actions, true)) {
                    $actions[] = $action;
                }
            }
        }

        return $actions;
    }

    /**
     * @param  Collection<int, Violation>  $chunkViolations
     * @return list<array{path: string, mime: string}>
     */
    private function evidenceForViolations(SafetyRecord $record, Collection $chunkViolations): array
    {
        $violationIds = $chunkViolations
            ->map(fn (Violation $violation) => (string) $violation->id)
            ->all();

        return $record->getMedia('violation_evidence')
            ->filter(function (Media $media) use ($violationIds) {
                $violationId = (string) ($media->getCustomProperty('violation_id') ?? '');

                return $violationId !== '' && in_array($violationId, $violationIds, true);
            })
            ->map(function (Media $media) {
                $path = $media->getPath();
                if (! is_string($path) || $path === '' || ! is_file($path)) {
                    return null;
                }

                $mime = $media->mime_type ?: 'image/jpeg';
                $binary = @file_get_contents($path);
                if ($binary === false || $binary === '') {
                    return null;
                }

                // Data URI is more reliable for mPDF than Windows filesystem paths.
                return [
                    'path' => 'data:'.$mime.';base64,'.base64_encode($binary),
                    'mime' => $mime,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function renderPdf(array $payload): string
    {
        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $fontDirs = (new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'];
        $fontDirs[] = 'C:/Windows/Fonts';

        $fontData = (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'];
        $defaultFont = 'dejavusans';

        // Prefer Windows Arial for sharper embedded Arabic/Latin text in PDF.
        if (is_file('C:/Windows/Fonts/arial.ttf')) {
            $fontData['arial'] = [
                'R' => 'arial.ttf',
                'B' => is_file('C:/Windows/Fonts/arialbd.ttf') ? 'arialbd.ttf' : 'arial.ttf',
                'useOTL' => 0xFF,
                'useKashida' => 75,
            ];
            $defaultFont = 'arial';
        } elseif (is_file('C:/Windows/Fonts/tahoma.ttf')) {
            $fontData['tahoma'] = [
                'R' => 'tahoma.ttf',
                'B' => is_file('C:/Windows/Fonts/tahomabd.ttf') ? 'tahomabd.ttf' : 'tahoma.ttf',
                'useOTL' => 0xFF,
                'useKashida' => 75,
            ];
            $defaultFont = 'tahoma';
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'tempDir' => $tempDir,
            'fontDir' => $fontDirs,
            'fontdata' => $fontData,
            'default_font' => $defaultFont,
            'default_font_size' => 10,
            'dpi' => 120,
            'img_dpi' => 120,
            'autoScriptToLang' => true,
            'autoLangToFont' => false,
            'autoArabic' => true,
            'useSubstitutions' => false,
            'margin_left' => 6,
            'margin_right' => 6,
            'margin_top' => 6,
            'margin_bottom' => 6,
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle('محضر مخالفة');
        $mpdf->SetCreator('Constrix Safety');
        // Keep table geometry fixed — never shrink to fit content.
        $mpdf->shrink_tables_to_fit = 0;

        ini_set('pcre.backtrack_limit', '50000000');

        $html = view('project-type::pdf.safety-violation-report', $payload)->render();
        $mpdf->WriteHTML($html);

        $gallery = $payload['gallery_evidence'] ?? [];
        if ($gallery !== []) {
            foreach (array_chunk($gallery, self::EVIDENCE_PER_GALLERY_PAGE) as $pageEvidence) {
                $galleryHtml = view('project-type::pdf.safety-violation-evidence', [
                    'header' => $payload['header'],
                    'evidence' => $pageEvidence,
                ])->render();
                $mpdf->AddPage();
                $mpdf->WriteHTML($galleryHtml);
            }
        }

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    /**
     * @param  list<array{filename: string, content: string}>  $pdfBinaries
     */
    private function zipResponse(string $safetyRecordId, array $pdfBinaries): BinaryFileResponse
    {
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $zipFileName = sprintf('safety-violation-reports-%s.zip', $safetyRecordId);
        $zipPath = $tempDir.DIRECTORY_SEPARATOR.$zipFileName;
        $tempPdfPaths = [];

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw SafetyException::reportGenerationFailed();
        }

        foreach ($pdfBinaries as $pdf) {
            $tempPdfPath = $tempDir.DIRECTORY_SEPARATOR.$pdf['filename'];
            file_put_contents($tempPdfPath, $pdf['content']);
            $tempPdfPaths[] = $tempPdfPath;
            $zip->addFile($tempPdfPath, $pdf['filename']);
        }

        $zip->close();

        register_shutdown_function(static function () use ($tempPdfPaths) {
            foreach ($tempPdfPaths as $tempPdfPath) {
                if (is_file($tempPdfPath)) {
                    @unlink($tempPdfPath);
                }
            }
        });

        return response()->download($zipPath, $zipFileName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    private function resolveReportLogoDataUri(): ?string
    {
        $path = base_path('modules/Project/ProjectType/Resources/assets/images/se-logo.png');

        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }
}
