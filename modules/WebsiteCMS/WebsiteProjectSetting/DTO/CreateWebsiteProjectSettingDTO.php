<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProjectSetting\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateWebsiteProjectSettingDTO
{
    public function __construct(
        public readonly array $name,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
