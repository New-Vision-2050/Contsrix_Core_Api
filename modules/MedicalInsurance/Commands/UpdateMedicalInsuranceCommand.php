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
        private ?string $provider = null,
        private ?string $employeeId = null,
        private ?string $startDate = null,
        private ?string $endDate = null,
        private ?float $value = null,
        private ?int $individualsCount = null,
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

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function getEmployeeId(): ?string
    {
        return $this->employeeId;
    }

    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function getIndividualsCount(): ?int
    {
        return $this->individualsCount;
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
            'provider' => $this->provider,
            'employee_id' => $this->employeeId,
        ];

        if ($this->startDate !== null) {
            $data['start_date'] = $this->startDate;
        }

        if ($this->endDate !== null) {
            $data['end_date'] = $this->endDate;
        }

        if ($this->value !== null) {
            $data['value'] = $this->value;
        }

        if ($this->individualsCount !== null) {
            $data['individuals_count'] = $this->individualsCount;
        }

        if ($this->status !== null) {
            $data['status'] = $this->status;
        }

        return $data;
    }
}
