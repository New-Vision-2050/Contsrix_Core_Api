<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateMedicalInsuranceCategoryCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private float $coverageLimit,
        private string $description,
        private ?string $type = null,
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getCoverageLimit(): float
    {
        return $this->coverageLimit;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function toArray(): array
    {
        return [
            'name'           => $this->name,
            'type'           => $this->type,
            'coverage_limit' => $this->coverageLimit,
            'description'    => $this->description,
        ];
    }
}
