<?php

declare(strict_types=1);

namespace Modules\TermServiceSetting\DTO;

class CreateTermServiceSettingDTO
{
    public function __construct(
        public readonly string $name,
        public readonly array $termSettingIds = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTermSettingIds(): array
    {
        return $this->termSettingIds;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
