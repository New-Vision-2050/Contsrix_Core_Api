<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\DTO\CompanyProfile;

use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Http\UploadedFile;
class CreateCompanyOfficialDocumentDTO
{
    public function __construct(
        private ManagementHierarchy $managementHierarchy,
        private ?string        $name,
        private string        $description,
        private string        $documentNumber,
        private string        $startDate,
        private string        $endDate,
        private string        $notificationDate,
        private UuidInterface $documentTypeId,
        public ?UploadedFile $files
    )
    {
    }

    public function getId()
    {
        return $this->managementHierarchy->company_id;
    }

    public function getFiles()
    {
        return $this->files;
    }


    public function toArray(): array
    {
        return [
            "company_id" => $this->managementHierarchy->company_id,
            "management_hierarchy_id" => $this->managementHierarchy->id,
            "name" => $this->name,
            "description" => $this->description,
            "document_number" => $this->documentNumber,
            "start_date" => $this->startDate,
            "end_date" => $this->endDate,
            "notification_date" => $this->notificationDate,
            "document_type_id" => $this->documentTypeId,

                  ];
    }
}
