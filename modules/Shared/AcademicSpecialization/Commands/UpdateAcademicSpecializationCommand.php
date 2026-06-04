<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateAcademicSpecializationCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?array $name = null,
        private ?string $code = null,
        private ?string $academicQualificationId = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): ?array
    {
        return $this->name;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getAcademicQualificationId(): ?string
    {
        return $this->academicQualificationId;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'code' => $this->code,
            'academic_qualification_id' => $this->academicQualificationId,
        ], fn($value) => $value !== null);
    }
}
