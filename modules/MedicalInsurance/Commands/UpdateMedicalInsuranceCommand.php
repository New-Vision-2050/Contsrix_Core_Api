<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateMedicalInsuranceCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private string $policyNumber,
        private string $employeeId,
        private ?string $endDate = null,
        private ?int $status = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPolicyNumber(): string
    {
        return $this->policyNumber;
    }

    public function getEmployeeId(): string
    {
        return $this->employeeId;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'policy_number' => $this->policyNumber,
            'employee_id' => $this->employeeId,
        ];

        if ($this->endDate !== null) {
            $data['end_date'] = $this->endDate;
        }

        if ($this->status !== null) {
            $data['status'] = $this->status;
        }

        return $data;
    }
}
