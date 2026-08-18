<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectType\Mail\SafetyViolationMail;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectType\Models\SafetyRecord;
use Modules\Project\ProjectType\Models\Violation;
use Throwable;

class SafetyViolationEmailService
{
    private const FOUND_STATUS = 'violation_found';

    public function __construct(
        private SafetyViolationReportUrlResolver $reportUrlResolver,
    ) {}

    /**
     * Send the contractor safety-violation notice after a successful evaluation.
     * Failures are logged and never bubbled to the caller.
     */
    public function sendAfterEvaluation(SafetyRecord $record): void
    {
        try {
            $record->loadMissing([
                'violations',
                'contractor',
                'project.manager',
                'assignedUser',
                'morphable',
            ]);

            if ($record->morphable instanceof ProjectOrderPermit) {
                $record->morphable->loadMissing(['projectDistrict']);
            }

            $foundViolations = $this->foundViolations($record);

            if ($foundViolations->isEmpty()) {
                return;
            }

            $contractorEmail = trim((string) ($record->contractor?->email ?? ''));
            if ($contractorEmail === '') {
                Log::warning('Safety violation email skipped: contractor has no email.', [
                    'safety_record_id' => $record->id,
                    'contractor_id' => $record->contractor_id,
                ]);

                return;
            }

            $data = $this->buildMailData($record, $foundViolations);

            Mail::to($contractorEmail)->send(new SafetyViolationMail($data));

            Log::info('Safety violation email sent.', [
                'safety_record_id' => $record->id,
                'contractor_email' => $contractorEmail,
                'found_violations' => $foundViolations->count(),
                'report_url' => $data['report_url'],
            ]);
        } catch (Throwable $e) {
            Log::error('Safety violation email failed.', [
                'safety_record_id' => $record->id,
                'error' => $e->getMessage(),
            ]);
        }
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
     * @return array{
     *     contractor_name: string,
     *     work_order: string,
     *     notification_type: string,
     *     issue_date: string,
     *     visit_time: string,
     *     location: string,
     *     project_manager: string,
     *     safety_officer: string,
     *     site_supervisor: string,
     *     total_fine: string,
     *     first_violation_code: string,
     *     violations: list<array{label: string, value: string}>,
     *     report_url: string|null
     * }
     */
    private function buildMailData(SafetyRecord $record, Collection $foundViolations): array
    {
        $morphable = $record->morphable;

        $workOrder = (string) (
            $morphable?->name
            ?? $morphable?->notification_number
            ?? ''
        );

        $location = '';
        if ($morphable instanceof ProjectNotification) {
            $location = (string) (
                $morphable->district
                ?: $morphable->full_address
                ?: $morphable->repair_point
                ?: ''
            );
        } elseif ($morphable instanceof ProjectOrderPermit) {
            $location = (string) ($morphable->projectDistrict?->name ?? '');
        }

        $timeSource = $record->inspection_time ?: $record->time;
        if (is_string($timeSource) && strlen($timeSource) >= 5) {
            $timeSource = substr($timeSource, 0, 5);
        }

        $visitTime = '';
        if (is_string($timeSource) && preg_match('/^\d{2}:\d{2}/', $timeSource) === 1) {
            $visitTime = date('h:i A', strtotime($timeSource));
        }

        $issueDate = $record->inspection_date
            ? $record->inspection_date->format('d/m/Y')
            : ($record->date?->format('d/m/Y') ?? '');

        $totalFine = $foundViolations->sum(
            fn (Violation $violation) => abs((float) ($violation->pivot->weight ?? $violation->default_weight ?? 0))
        ) * 1000;

        /** @var Violation $first */
        $first = $foundViolations->first();

        $consultantEngineer = (string) ($record->consultant_engineer ?? '');

        return [
            'contractor_name' => (string) ($record->contractor?->name ?? ''),
            'work_order' => $workOrder,
            'notification_type' => 'مخالفة سلامة',
            'issue_date' => $issueDate,
            'visit_time' => $visitTime,
            'location' => $location,
            'project_manager' => (string) ($record->project?->manager?->name ?? ''),
            'safety_officer' => (string) ($record->assignedUser?->name ?? $consultantEngineer),
            'site_supervisor' => $consultantEngineer,
            'total_fine' => $this->formatPenalty((float) $totalFine),
            'first_violation_code' => (string) ($first->code ?? ''),
            'violations' => $this->mapViolationRows($foundViolations),
            'report_url' => $this->resolveReportUrl($record),
        ];
    }

    private function resolveReportUrl(SafetyRecord $record): ?string
    {
        try {
            $url = $this->reportUrlResolver->storeAndGetPublicUrl($record);

            return $url !== '' ? $url : null;
        } catch (Throwable $e) {
            Log::error('Failed to store safety violation report for email link.', [
                'safety_record_id' => $record->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  Collection<int, Violation>  $foundViolations
     * @return list<array{label: string, value: string}>
     */
    private function mapViolationRows(Collection $foundViolations): array
    {
        $rows = [];

        foreach ($foundViolations->values() as $index => $violation) {
            $code = (string) $violation->code;
            $description = (string) $violation->description;

            if ($index === 0) {
                $rows[] = [
                    'label' => 'وصف المخالفة',
                    'value' => $description,
                ];
                continue;
            }

            $rows[] = [
                'label' => $this->violationOrdinalLabel($index + 1),
                'value' => trim($code.' - '.$description),
            ];
        }

        return $rows;
    }

    private function violationOrdinalLabel(int $number): string
    {
        $ordinals = [
            2 => 'الثانية',
            3 => 'الثالثة',
            4 => 'الرابعة',
            5 => 'الخامسة',
            6 => 'السادسة',
            7 => 'السابعة',
            8 => 'الثامنة',
            9 => 'التاسعة',
            10 => 'العاشرة',
            11 => 'الحادية عشرة',
            12 => 'الثانية عشرة',
            13 => 'الثالثة عشرة',
            14 => 'الرابعة عشرة',
            15 => 'الخامسة عشرة',
            16 => 'السادسة عشرة',
            17 => 'السابعة عشرة',
            18 => 'الثامنة عشرة',
            19 => 'التاسعة عشرة',
            20 => 'العشرون',
        ];

        return 'المخالفة '.($ordinals[$number] ?? (string) $number);
    }

    private function formatPenalty(float $value): string
    {
        if ((float) (int) $value === $value) {
            return (string) (int) $value;
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
