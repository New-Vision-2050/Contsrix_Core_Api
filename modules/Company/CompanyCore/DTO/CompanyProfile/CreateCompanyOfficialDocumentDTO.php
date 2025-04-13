<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\DTO\CompanyProfile;

use Ramsey\Uuid\UuidInterface;

class CreateCompanyOfficialDocumentDTO
{
    public function __construct(
        private UuidInterface $id,
        private string        $name,
        private string        $description,
        private string        $documentNumber,
        private string        $startDate,
        private string        $endDate,
        private string        $notificationDate,
        private UuidInterface $documentTypeId,
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
            "company_id" => $this->id,
            "name" => $this->name,
            "description" => $this->description,
            "document_number" => $this->documentNumber,
            "start_date" => $this->startDate,
            "end_date" => $this->endDate,
            "notification_date" => $this->notificationDate,
            "document_type_id" => $this->documentTypeId

                  ];
    }
}
