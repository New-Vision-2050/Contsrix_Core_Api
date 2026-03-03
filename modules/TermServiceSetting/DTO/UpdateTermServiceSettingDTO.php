<?php

declare(strict_types=1);

namespace Modules\TermServiceSetting\DTO;

class UpdateTermServiceSettingDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly array $termSettingIds = [],
    ) {
    }

    public function getId(): int
    {
        return $this->id;
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
