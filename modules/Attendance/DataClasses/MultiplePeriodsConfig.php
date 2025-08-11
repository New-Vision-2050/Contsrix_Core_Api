<?php

declare(strict_types=1);

namespace Modules\Attendance\DataClasses;

use InvalidArgumentException;
use JsonSerializable; // تأكد من استيرادها إذا كنت تستخدمها

class MultiplePeriodsConfig implements JsonSerializable
{
    public WeeklySchedule $weeklySchedule;
    public array $holidays;
    public array $overtime_rules;
    public array $out_zone_rules;
    public array $early_clock_in_rules;
    public array $lateness_rules;
    public string $subtype;

    public function __construct(
        string $subtype,
        WeeklySchedule $weeklySchedule,
        array $holidays = [],
        array $overtimeRules = [],
        array $outZoneRules = [],
        array $earlyClockInRules = [],
        array $latenessRules = []
    ) {
        $this->subtype = $subtype;
        $this->weeklySchedule = $weeklySchedule;
        $this->holidays = $holidays;
        $this->overtime_rules = $overtimeRules;
        $this->out_zone_rules = $outZoneRules;
        $this->early_clock_in_rules = $earlyClockInRules;
        $this->lateness_rules = $latenessRules;
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['subtype']) || $data['subtype'] !== 'multiple_periods') {
            throw new InvalidArgumentException("Subtype must be 'multiple_periods'.");
        }
        if (!isset($data['weekly_schedule']) || !is_array($data['weekly_schedule'])) {
            throw new InvalidArgumentException("Missing 'weekly_schedule' in MultiplePeriodsConfig.");
        }

        $weeklySchedule = WeeklySchedule::fromArray($data['weekly_schedule']);

        return new self(
            $data['subtype'],
            $weeklySchedule,
            $data['holidays'] ?? [],
            $data['overtime_rules'] ?? [],
            $data['out_zone_rules'] ?? [],
            $data['early_clock_in_rules'] ?? [],
            $data['lateness_rules'] ?? []
        );
    }

    public function toJson(int $flags = JSON_PRETTY_PRINT): string
    {
        return json_encode($this->jsonSerialize(), $flags);
    }

    public function jsonSerialize(): array
    {
        return [
            'subtype' => $this->subtype,
            'weekly_schedule' => $this->weeklySchedule->toArray(),
            'holidays' => $this->holidays,
            'overtime_rules' => $this->overtime_rules,
            'out_zone_rules' => $this->out_zone_rules,
            'early_clock_in_rules' => $this->early_clock_in_rules,
            'lateness_rules' => $this->lateness_rules,
        ];
    }

    // --- Proxy methods to WeeklySchedule ---
    public function getTotalWeeklyWorkHours(): float
    {
        // هذه هي الطريقة الصحيحة للوصول إلى الدوال في WeeklySchedule
        return $this->weeklySchedule->getTotalWeeklyWorkHours();
    }

    public function getEnabledDays(): array
    {
        return $this->weeklySchedule->getEnabledDays();
    }

    public function hasCrossDayPeriods(): bool
    {
        return $this->weeklySchedule->hasCrossDayPeriods();
    }
}
