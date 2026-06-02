<?php

declare(strict_types=1);

namespace Modules\Shared\University\DTO;

class CreateUniversityDTO
{
    public function __construct(
        public string $name,
        public string $countryId,
        public string $url,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'country_id' => $this->countryId,
            'url' => $this->url,
        ];
    }
}
