<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Commands\CompanyProfile;

use Ramsey\Uuid\UuidInterface;

class UpdateCompanyLegalDataCommand
{
    public function __construct(
        private array         $data

    )
    {
    }



    public function toArray(): array
    {
        return $this->data;
    }
}
