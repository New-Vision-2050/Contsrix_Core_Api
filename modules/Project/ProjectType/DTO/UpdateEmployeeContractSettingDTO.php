<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\DTO;

class UpdateEmployeeContractSettingDTO
{
    public function __construct(
        public readonly ?bool $is_all_data_visible = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'is_all_data_visible' => $this->is_all_data_visible,
        ], fn($value) => $value !== null);
    }
}
