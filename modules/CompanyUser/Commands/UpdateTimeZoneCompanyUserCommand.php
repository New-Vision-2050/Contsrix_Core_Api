<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateTimeZoneCompanyUserCommand
{
    public function __construct(
        private UuidInterface $id,
        public string $country_id ,
        public string $time_zone_id,
        public string $language_id,
        public string $currency_id,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'country_id' => $this->country_id,
            'time_zone_id' => $this->time_zone_id,
            'language_id' => $this->language_id,
            'currency_id' => $this->currency_id,
        ];
    }
}
