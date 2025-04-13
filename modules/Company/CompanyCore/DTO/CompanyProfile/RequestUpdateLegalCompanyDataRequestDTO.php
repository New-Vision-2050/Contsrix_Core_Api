<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\DTO\CompanyProfile;

use Ramsey\Uuid\UuidInterface;

class RequestUpdateLegalCompanyDataRequestDTO
{
    public function __construct(
        private UuidInterface $id,
        private UuidInterface $registrationTypeId,
        private string        $registrationNo,
        private string        $registrationNoStartDate,
        private string        $registrationNoEndDate,
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
            "registration_no" => $this->registrationNo,
            "registration_no_start_date" => $this->registrationNoStartDate,
            "registration_no_end_date" => $this->registrationNoEndDate,
        ];
    }
}
