<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\DTO\CompanyProfile;

use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Ramsey\Uuid\UuidInterface;
use Carbon\Carbon;
class CreateCompanyLegalDataDTO
{
    public function __construct(
        private ManagementHierarchy $managementHierarchy,
        private ?UuidInterface $registrationTypeId = null,
        private ?string $registrationNumber = null,
        private ?string $startDate = null,
        private ?string $endDate = null,
        private ?array $files = null
    ) {
    }

    public function getId()
    {
        return $this->managementHierarchy->company_id;
    }

    public function getFiles(): ?array
    {
        return $this->files;
    }

    public function toArray(): array
    {
        return [
            "company_id" => $this->managementHierarchy->company_id,
            "management_hierarchy_id" => $this->managementHierarchy->id,
            "registration_type_id" => $this->registrationTypeId,
            "registration_number" => $this->registrationNumber,
            "start_date" => $this->startDate ? Carbon::parse($this->startDate)->format('Y-m-d') : null,
            "end_date" => $this->endDate ? Carbon::parse($this->endDate)->format('Y-m-d') : null,
        ];
    }
}
