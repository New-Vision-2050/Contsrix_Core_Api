<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\DTO\CompanyProfile;

use Ramsey\Uuid\UuidInterface;

class RequestUpdateLegalCompanyDataRequestDTO
{
    public function __construct(
        private UuidInterface $id,
        private array $data,

    )
    {
    }

    public function getId()
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
