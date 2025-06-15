<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\DTO\CompanyProfile;

use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Http\UploadedFile;

class AssignLogoToCompanyDTO
{
    public function __construct(
        public ManagementHierarchy $managementHierarchy,
        public UploadedFile $logo,
    ) {
    }

    public function getLogo()
    {
        return $this->logo;
    }

    public function getId()
    {
        return $this->managementHierarchy->company_id;
    }

}
