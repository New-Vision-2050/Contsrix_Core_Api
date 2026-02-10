<?php

declare(strict_types=1);

namespace Modules\CompanyUser\DTO\Client;


use Ramsey\Uuid\UuidInterface;

class CreateClientCompanyDTO
{
    public function __construct(

        public UuidInterface $userId,
        public UuidInterface $companyId,


    )
    {
    }




}
