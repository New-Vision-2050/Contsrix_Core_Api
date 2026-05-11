<?php

declare(strict_types=1);

namespace Modules\Reports\DTO;

/**
 * Step 2 — Employee filters (بيانات الموظفين).
 */
final class ReportWizardStep2DTO
{
    public function __construct(
        public readonly string  $employeeScope,            // all|active|inactive|on_leave|dismissed
        /** @var string[] */
        public readonly array   $employeeUserIds,          // specific user global_ids (empty = all)
        public readonly ?string $branchId,                 // branch_id (uuid)
        public readonly ?string $managementId,             // management_id (uuid)
        public readonly ?string $department,               // department_id
        public readonly ?string $jobTitle,                 // job_title_id
        /** @var string[] */
        public readonly array   $contractTypeIds,
        public readonly ?string $nationality,              // country_id or ISO slug
        public readonly ?string $gender,                   // male|female
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            employeeScope:   (string) ($payload['employee_scope']    ?? $payload['employeeStatus'] ?? 'all'),
            employeeUserIds: array_values($payload['employee_user_ids'] ?? []),
            branchId:        $payload['branch_id']      ?? $payload['location']    ?? null,
            managementId:    $payload['management_id']  ?? $payload['management']  ?? null,
            department:      $payload['department']     ?? null,
            jobTitle:        $payload['job_title']      ?? $payload['jobTitle']    ?? null,
            contractTypeIds: array_values($payload['contractTypeIds'] ?? []),
            nationality:     $payload['nationality']    ?? null,
            gender:          $payload['gender']         ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'employee_scope'    => $this->employeeScope,
            'employee_user_ids' => $this->employeeUserIds,
            'branch_id'         => $this->branchId,
            'management_id'     => $this->managementId,
            'department'        => $this->department,
            'job_title'         => $this->jobTitle,
            'contractTypeIds'   => $this->contractTypeIds,
            'nationality'       => $this->nationality,
            'gender'            => $this->gender,
        ];
    }
}
