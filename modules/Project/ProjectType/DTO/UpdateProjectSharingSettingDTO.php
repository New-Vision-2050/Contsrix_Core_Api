<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\DTO;

class UpdateProjectSharingSettingDTO
{
    public function __construct(
        public readonly ?int $is_enabled = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'is_enabled' => $this->is_enabled,
        ], fn($value) => $value !== null);
    }
}
