<?php

declare(strict_types=1);

namespace Modules\Reports\DTO;

/**
 * Step 3 — Attendance data filters (بيانات الحضور).
 */
final class ReportWizardStep3DTO
{
    public function __construct(
        /** @var string[] */
        public readonly array  $attendanceDataTypeIds,
        public readonly string $displayMode,
        public readonly string $attendancePattern,
        public readonly string $attendanceRateMin,
        public readonly string $delayLimitMinutes,
        public readonly string $minOvertime,
        public readonly bool   $includeEntryExitTime,
        public readonly bool   $includeShiftName,
        public readonly bool   $includeAttendanceNotes,
        public readonly bool   $calculateTotalWorkHours,
        public readonly bool   $showPreviousMonthComparison,
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            attendanceDataTypeIds:       array_values($payload['attendanceDataTypeIds'] ?? []),
            displayMode:                 (string) ($payload['display_mode']          ?? 'all_employees'),
            attendancePattern:           (string) ($payload['attendancePattern']     ?? 'all'),
            attendanceRateMin:           (string) ($payload['attendanceRateMin']     ?? 'no_filter'),
            delayLimitMinutes:           (string) ($payload['delayLimitMinutes']     ?? 'no_filter'),
            minOvertime:                 (string) ($payload['minOvertime']           ?? 'no_filter'),
            includeEntryExitTime:        (bool)   ($payload['includeEntryExitTime']        ?? true),
            includeShiftName:            (bool)   ($payload['includeShiftName']            ?? true),
            includeAttendanceNotes:      (bool)   ($payload['includeAttendanceNotes']      ?? false),
            calculateTotalWorkHours:     (bool)   ($payload['calculateTotalWorkHours']     ?? true),
            showPreviousMonthComparison: (bool)   ($payload['showPreviousMonthComparison'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'attendanceDataTypeIds'       => $this->attendanceDataTypeIds,
            'display_mode'               => $this->displayMode,
            'attendancePattern'           => $this->attendancePattern,
            'attendanceRateMin'           => $this->attendanceRateMin,
            'delayLimitMinutes'           => $this->delayLimitMinutes,
            'minOvertime'                 => $this->minOvertime,
            'includeEntryExitTime'        => $this->includeEntryExitTime,
            'includeShiftName'            => $this->includeShiftName,
            'includeAttendanceNotes'      => $this->includeAttendanceNotes,
            'calculateTotalWorkHours'     => $this->calculateTotalWorkHours,
            'showPreviousMonthComparison' => $this->showPreviousMonthComparison,
        ];
    }
}
