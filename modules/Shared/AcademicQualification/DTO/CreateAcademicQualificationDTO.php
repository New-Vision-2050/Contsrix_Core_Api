<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicQualification\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateAcademicQualificationDTO
{
    public function __construct(
        public string $name,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => ["ar"=>$this->name , "en"=>$this->name],
        ];
    }
}
