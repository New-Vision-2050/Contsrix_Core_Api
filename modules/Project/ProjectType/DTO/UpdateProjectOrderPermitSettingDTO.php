<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\DTO;

class UpdateProjectOrderPermitSettingDTO
{
    public function __construct(public readonly ?bool $is_shown = null) {}

    public function toArray(): array
    {
        return array_filter(['is_shown' => $this->is_shown], fn ($value) => $value !== null);
    }
}