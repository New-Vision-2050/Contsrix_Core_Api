<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateTermSettingDTO
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
