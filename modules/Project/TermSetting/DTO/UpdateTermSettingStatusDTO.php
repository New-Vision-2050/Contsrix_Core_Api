<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\DTO;

class UpdateTermSettingStatusDTO
{
    public function __construct(
        public int $id,
        public int $isActive,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getIsActive(): int
    {
        return $this->isActive;
    }

    public function __toString(): string
    {
        return json_encode([
            'id' => $this->id,
            'is_active' => $this->isActive,
        ]);
    }
}
