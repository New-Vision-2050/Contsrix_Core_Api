<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateEcoAppSettingDTO
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
