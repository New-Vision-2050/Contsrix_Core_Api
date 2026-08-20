<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectType\Exceptions\SafetyException;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectType\Models\SafetyRecord;
use Modules\Project\ProjectType\Models\Violation;
use Modules\Project\ProjectType\Support\SafetyPdfFonts;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * SEC-style "نموذج محضر مخالفة سلامة" PDF (exact-template design).
 * Reuses the same SafetyRecord / found-violations business rules as
 * SafetyViolationReportService, but renders a separate template.
 */
class SafetyViolationFormReportService
{
    private const FOUND_STATUS = 'violation_found';

    private const BODY_ROWS = 8;

    private const EVIDENCE_PER_PAGE = 4;

    private const WEIGHT_MULTIPLIER = 1000;

    public function download(string $projectId, string $safetyRecordId): Response
    {
        $file = $this->buildDownloadableFile($projectId, $safetyRecordId);

        return response($file['content'], 200, [
            'Content-Type' => $file['mime'],
            'Content-Disposition' => 'attachment; filename="'.$file['filename'].'"',
        ]);
    }

    /**
     * Generate the Jeddah form PDF, store it on a public disk,
     * and return a URL that can be opened from an email without API auth.
     */
    public function storeAndGetPublicUrl(string $projectId, string $safetyRecordId): string
    {
        $file = $this->buildDownloadableFile($projectId, $safetyRecordId);
        $record = $this->loadRecord($projectId, $safetyRecordId);

        $bucket = config('filesystems.disks.s3_public.bucket');
        $disk = (is_string($bucket) && $bucket !== '') ? 's3_public' : 'public';

        $record->clearMediaCollection(SafetyRecord::VIOLATION_REPORT_COLLECTION);

        $media = $record
            ->addMediaFromString($file['content'])
            ->usingFileName($file['filename'])
            ->usingName('violation-form-report')
            ->withCustomProperties([
                'file_path' => 'safety/violation-reports/'.$record->project_id,
                'mime' => $file['mime'],
            ])
            ->toMediaCollection(SafetyRecord::VIOLATION_REPORT_COLLECTION, $disk);

        return $media->getFullUrl();
    }

    /**
     * @return array{filename: string, content: string, mime: string}
     */
    public function buildDownloadableFile(string $projectId, string $safetyRecordId): array
    {
        $record = $this->loadRecord($projectId, $safetyRecordId);
        $foundViolations = $this->foundViolations($record);

        if ($foundViolations->isEmpty()) {
            throw SafetyException::noFoundViolations();
        }

        $payload = $this->buildPayload($record, $foundViolations);

        return [
            'filename' => sprintf('safety-violation-form-report-%s.pdf', $record->id),
            'content' => $this->renderPdf($payload),
            'mime' => 'application/pdf',
        ];
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
     * @param  Collection<int, Violation>  $foundViolations
     * @return array<string, mixed>
     */
    private function buildPayload(SafetyRecord $record, Collection $foundViolations): array
    {
        $header = $this->resolveHeader($record);
        $rows = $this->mapViolationRows($foundViolations);
        $grandTotal = collect($rows)->sum('total');

        $bodyRows = $rows;
        while (count($bodyRows) < self::BODY_ROWS) {
            $empty = $this->emptyViolationRow();
            $empty['serial'] = (string) (count($bodyRows) + 1);
            $bodyRows[] = $empty;
        }

        return [
            'header' => $header,
            'violation_rows' => $bodyRows,
            'grand_total' => $grandTotal,
            'grand_total_display' => $this->formatMoney($grandTotal),
            'evidence_pages' => $this->chunkEvidence(
                $this->evidenceForViolations($record, $foundViolations)
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveHeader(SafetyRecord $record): array
    {
        $morphable = $record->morphable;

        $workOrder = (string) (
            $morphable?->name
            ?? $morphable?->notification_number
            ?? ''
        );

        $location = '';
        $contractorSafetyRep = '';
        $office = '';
        $contractorName = (string) ($record->contractor?->name ?? '');
        $contractorContractNumber = (string) ($record->contractor?->number ?? '');
        $permitSource = (string) ($record->consultant_engineer ?? '');
        $permitRecipient = $contractorName;

        if ($morphable instanceof ProjectNotification) {
            $location = (string) (
                $morphable->district
                ?: $morphable->full_address
                ?: $morphable->repair_point
                ?: ''
            );
            $contractorSafetyRep = (string) ($morphable->contractorRepresentative?->name ?? '');
            if ($contractorContractNumber === '') {
                $contractorContractNumber = (string) ($morphable->contractor_number ?? '');
            }
        } elseif ($morphable instanceof ProjectOrderPermit) {
            $location = (string) ($morphable->projectDistrict?->name ?? '');
            $office = (string) ($morphable->office ?? '');
        }

        $timeSource = $record->inspection_time ?: $record->time;
        if (is_string($timeSource) && strlen($timeSource) >= 5) {
            $timeSource = substr($timeSource, 0, 5);
        }

        $formattedTime = '';
        if (is_string($timeSource) && preg_match('/^\d{2}:\d{2}/', $timeSource) === 1) {
            $formattedTime = date('h:i A', strtotime($timeSource));
        }

        $inspectionDate = $record->inspection_date
            ? $record->inspection_date->format('d/m/Y')
            : ($record->date?->format('d/m/Y') ?? '');

        $assignedUser = $record->assignedUser;
        $preparerName = (string) ($assignedUser?->name ?? $record->consultant_engineer ?? '');
        $preparerJobCode = (string) ($assignedUser?->professionalData?->job_code ?? '');

        return [
            'logo' => $this->resolveReportLogoDataUri(),
            'report_number' => $this->resolveReportNumber($workOrder),
            'department' => '',
            'circle' => '',
            'projects' => '',
            'office' => $office,
            'contractor_name' => $contractorName,
            'contractor_contract_number' => $contractorContractNumber,
            'visit_location' => $location,
            'visit_time' => $formattedTime,
            'visit_date' => $inspectionDate,
            'permit_source' => $permitSource,
            'permit_recipient' => $permitRecipient,
            'contractor_safety_rep' => $contractorSafetyRep,
            'work_order' => $workOrder,
            'preparer_name' => $preparerName,
            'preparer_job_code' => $preparerJobCode,
            'preparer_signature' => $preparerName,
            'recipient_name' => $contractorName,
            'recipient_job_or_iqama' => '',
            'recipient_signature' => $contractorName,
            'auth_time' => $formattedTime,
            'auth_date' => $inspectionDate,
            'notes' => '',
        ];
    }

    private function resolveReportNumber(string $workOrder): string
    {
        $wo = trim($workOrder);
        if ($wo === '') {
            return '';
        }

        $normalized = preg_replace('/\s*\/\s*/', '-', $wo) ?? $wo;

        return 'PDC-'.$normalized;
    }

    /**
     * @param  Collection<int, Violation>  $foundViolations
     * @return list<array{serial: string, code: string, description: string, repetition: string, value: float, value_display: string, total: float, total_display: string}>
     */
    private function mapViolationRows(Collection $foundViolations): array
    {
        $rows = [];

        foreach ($foundViolations->values() as $index => $violation) {
            $value = abs((float) ($violation->pivot->weight ?? $violation->default_weight ?? 0))
                * self::WEIGHT_MULTIPLIER;
            $repetition = 1;
            $total = $value * $repetition;

            $rows[] = [
                'serial' => (string) ($index + 1),
                'code' => (string) $violation->code,
                'description' => (string) $violation->description,
                'repetition' => (string) $repetition,
                'value' => $value,
                'value_display' => $this->formatMoney($value),
                'total' => $total,
                'total_display' => $this->formatMoney($total),
            ];
        }

        return $rows;
    }

    /**
     * @return array{serial: string, code: string, description: string, repetition: string, value: float, value_display: string, total: float, total_display: string}
     */
    private function emptyViolationRow(): array
    {
        return [
            'serial' => '',
            'code' => '',
            'description' => '',
            'repetition' => '',
            'value' => 0.0,
            'value_display' => '',
            'total' => 0.0,
            'total_display' => '',
        ];
    }

    private function formatMoney(float $value): string
    {
        if ($value == 0.0) {
            return '';
        }

        if ((float) (int) $value === $value) {
            return number_format((int) $value, 0, '.', ',');
        }

        return number_format($value, 2, '.', ',');
    }

    /**
     * @param  Collection<int, Violation>  $foundViolations
     * @return list<array{path: string, mime: string}>
     */
    private function evidenceForViolations(SafetyRecord $record, Collection $foundViolations): array
    {
        $violationIds = $foundViolations
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
     * @param  list<array{path: string, mime: string}>  $evidence
     * @return list<list<array{path: string, mime: string}|null>>
     */
    private function chunkEvidence(array $evidence): array
    {
        if ($evidence === []) {
            return [];
        }

        $pages = [];
        foreach (array_chunk($evidence, self::EVIDENCE_PER_PAGE) as $chunk) {
            while (count($chunk) < self::EVIDENCE_PER_PAGE) {
                $chunk[] = null;
            }
            $pages[] = array_values($chunk);
        }

        return $pages;
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

        $fonts = SafetyPdfFonts::mpdfConfig();

        // Reference PDF uses Letter (612 x 792), not A4.
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'Letter',
            'orientation' => 'P',
            'tempDir' => $tempDir,
            'fontDir' => $fonts['fontDir'],
            'fontdata' => $fonts['fontdata'],
            'default_font' => $fonts['default_font'],
            'default_font_size' => 10,
            'dpi' => 96,
            'img_dpi' => 96,
            'autoScriptToLang' => true,
            'autoLangToFont' => false,
            'autoArabic' => true,
            'useSubstitutions' => true,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 12,
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle('نموذج محضر مخالفة سلامة');
        $mpdf->SetCreator('Constrix Safety');
        $mpdf->shrink_tables_to_fit = 0;

        ini_set('pcre.backtrack_limit', '50000000');

        $html = view('project-type::pdf.safety-violation-form-report', $payload)->render();
        $mpdf->WriteHTML($html);

        foreach ($payload['evidence_pages'] as $pageEvidence) {
            $galleryHtml = view('project-type::pdf.safety-violation-form-evidence', [
                'evidence' => $pageEvidence,
            ])->render();
            $mpdf->AddPage();
            $mpdf->WriteHTML($galleryHtml);
        }

        return $mpdf->Output('', Destination::STRING_RETURN);
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
