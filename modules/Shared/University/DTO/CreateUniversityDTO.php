<?php

declare(strict_types=1);

namespace Modules\Shared\University\DTO;

class CreateUniversityDTO
{
    public function __construct(
        public string $name,
        public string $countryIso2,
        public string $url,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'country_iso2' => $this->countryIso2,
            'url' => $this->url,
        ];
    }
}
