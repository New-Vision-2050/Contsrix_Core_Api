<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\DTO\CompanyProfile;

use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Ramsey\Uuid\Uuid;

class RequestUpdateLegalCompanyDataRequestDTO
{
    public function __construct(
        private ManagementHierarchy $managementHierarchy,
        private array $data,

    )
    {
    }

    public function getId()
    {
        return  $this->managementHierarchy->id;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
