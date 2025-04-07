<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\DTO\CompanyProfile;

use Ramsey\Uuid\UuidInterface;

class AssignLogoToCompanyDTO
{
    public function __construct(
        public UuidInterface $id,
        public  $logo,
    ) {
    }

    public function getLogo()
    {
        return $this->logo;
    }

    public function getId()
    {
        return $this->id;
    }

}
