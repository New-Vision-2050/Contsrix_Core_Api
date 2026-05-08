<?php

declare(strict_types=1);

namespace Modules\Reports\DTO;

/**
 * Step 2 — Employee filters (بيانات الموظفين).
 */
final class ReportWizardStep2DTO
{
    public function __construct(
        public readonly string  $employeeStatus,           // all|active|inactive|on_leave|dismissed
        public readonly ?string $location,                 // branch_id (uuid) or string slug like "jeddah"
        public readonly ?string $management,               // management_id
        public readonly ?string $department,               // department_id
        public readonly ?string $jobTitle,                 // job_title_id
        /** @var string[] */
        public readonly array   $contractTypeIds,
        public readonly ?string $nationality,              // country_id or "egyptian" / "saudi" slug
        public readonly ?string $gender,                   // male|female
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            employeeStatus:  (string) ($payload['employeeStatus'] ?? 'all'),
            location:        $payload['location']   ?? null,
            management:      $payload['management'] ?? null,
            department:      $payload['department'] ?? null,
            jobTitle:        $payload['jobTitle']   ?? null,
            contractTypeIds: array_values($payload['contractTypeIds'] ?? []),
            nationality:     $payload['nationality'] ?? null,
            gender:          $payload['gender']      ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'employeeStatus'  => $this->employeeStatus,
            'location'        => $this->location,
            'management'      => $this->management,
            'department'      => $this->department,
            'jobTitle'        => $this->jobTitle,
            'contractTypeIds' => $this->contractTypeIds,
            'nationality'     => $this->nationality,
            'gender'          => $this->gender,
        ];
    }
}
