<?php

declare(strict_types=1);

namespace Modules\Shared\University\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateUniversityCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $countryIso2,
        private string $name,
        private ?string $url = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
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
