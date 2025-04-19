<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Commands\CompanyProfile;

use Ramsey\Uuid\UuidInterface;

class UpdateCompanyOfficialDocumentCommand
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
        private               $files,
        private               $filesDeleteIds,
    )
    {
    }





    public function getId()
    {
        return $this->id;
    }

    public function getFiles()
    {
        return $this->files;
    }
    public function getDeletedFilesId()
    {
        return $this->filesDeleteIds;
    }


    public function toArray(): array
    {
        return [
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
