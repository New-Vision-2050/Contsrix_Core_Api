<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateAcademicSpecializationCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?array $name = null,
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

    public function getAcademicQualificationId(): ?string
    {
        return $this->academicQualificationId;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'academic_qualification_id' => $this->academicQualificationId,
        ];
    }
}
