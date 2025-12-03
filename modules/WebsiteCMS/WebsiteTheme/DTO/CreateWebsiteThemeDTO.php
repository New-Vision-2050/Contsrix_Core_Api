<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTheme\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateWebsiteThemeDTO
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
