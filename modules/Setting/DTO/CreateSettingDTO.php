<?php

declare(strict_types=1);

namespace Modules\Setting\DTO;

class CreateSettingDTO
{
    public function __construct(
        public string $key,
        public string $value,
    ) {
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
        ];
    }
}
