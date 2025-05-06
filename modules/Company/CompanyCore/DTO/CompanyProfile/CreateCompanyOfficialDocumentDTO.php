<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\DTO\CompanyProfile;

use Illuminate\Http\UploadedFile; // Add this import
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
// Remove: use Modules\Shared\Media\Services\FileUploadService; // No longer needed here
use Ramsey\Uuid\UuidInterface;

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
        /** @var UploadedFile|UploadedFile[]|null */ // PHPDoc for clarity if mixed
        private $files, // Changed: Expect UploadedFile or array of UploadedFile or null
    ) {
    }

    public function getId()
    {
        return $this->managementHierarchy->company_id;
    }

    /**
     * Returns the file(s) to be uploaded.
     * Could be a single UploadedFile or an array of UploadedFile objects.
     *
     * @return UploadedFile|UploadedFile[]|null
     */
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
