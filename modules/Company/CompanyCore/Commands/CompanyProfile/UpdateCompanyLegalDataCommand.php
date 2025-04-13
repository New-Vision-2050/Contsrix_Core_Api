<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Commands\CompanyProfile;

use Ramsey\Uuid\UuidInterface;

class UpdateCompanyLegalDataCommand
{
    public function __construct(
        private UuidInterface $id,//company_profile_id
        private string        $startDate,
        private string        $endDate,
        private               $file,

    )
    {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getFile()
    {
        return $this->file;
   }

    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,

        ];
    }
}
