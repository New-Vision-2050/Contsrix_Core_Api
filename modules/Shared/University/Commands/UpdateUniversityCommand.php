<?php

declare(strict_types=1);

namespace Modules\Shared\University\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateUniversityCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $countryId,
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
            'name' =>["ar"=> $this->name , "en"=> $this->name],
            'country_id' => $this->countryId,
            'link' => $this->url,
        ];
    }
}
