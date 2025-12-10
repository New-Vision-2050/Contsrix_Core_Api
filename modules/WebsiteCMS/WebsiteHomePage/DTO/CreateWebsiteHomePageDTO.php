<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateWebsiteHomePageDTO
{
    public function __construct(
        public string $name,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
