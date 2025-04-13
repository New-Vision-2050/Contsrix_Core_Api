<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\DTO\CompanyProfile;

use Ramsey\Uuid\UuidInterface;

class CreateCompanyLegalDataDTO
{
    public function __construct(
        private UuidInterface $id,
        private UuidInterface $registrationTypeId,
        private string        $registrationNumber,
        private string        $startDate,
        private string        $endDate,
        private               $file
    )
    {
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFile()
    {
        return $this->file;
    }


    public function toArray(): array
    {
        return [
            "registration_type_id" => $this->registrationTypeId,
            "registration_number" => $this->registrationNumber,
            "start_date" => $this->startDate,
            "end_date" => $this->endDate,
        ];
    }
}
