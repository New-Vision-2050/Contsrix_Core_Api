<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateAcademicSpecializationDTO
{
    public function __construct(
        public readonly array $name,
        public readonly string $code,
        public readonly ?string $academicQualificationId = null,
    ) {
    }

    public function getName(): array
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getAcademicQualificationId(): ?string
    {
        return $this->academicQualificationId;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'academic_qualification_id' => $this->academicQualificationId,
        ];
    }
}
