<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\DTO\CompanyProfile;

use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Ramsey\Uuid\UuidInterface;

class RequestUpdateLegalCompanyDataRequestDTO
{
    public function __construct(
        private ManagementHierarchy $managementHierarchy,
        private array $data,

    )
    {
    }

    public function getId() //TODO this would use branch id
    {
        return $this->managementHierarchy->company_id;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
