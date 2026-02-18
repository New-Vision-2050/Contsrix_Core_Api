<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\DTO;

class UpdateProjectDataSettingDTO
{
    public function __construct(
        public readonly ?bool $is_reference_number = null,
        public readonly ?bool $is_name_project = null,
        public readonly ?bool $is_client = null,
        public readonly ?bool $is_responsible_engineer = null,
        public readonly ?bool $is_number_contract = null,
        public readonly ?bool $is_central_cost = null,
        public readonly ?bool $is_project_value = null,
        public readonly ?bool $is_start_date = null,
        public readonly ?bool $is_achievement_percentage = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'is_reference_number' => $this->is_reference_number,
            'is_name_project' => $this->is_name_project,
            'is_client' => $this->is_client,
            'is_responsible_engineer' => $this->is_responsible_engineer,
            'is_number_contract' => $this->is_number_contract,
            'is_central_cost' => $this->is_central_cost,
            'is_project_value' => $this->is_project_value,
            'is_start_date' => $this->is_start_date,
            'is_achievement_percentage' => $this->is_achievement_percentage,
        ], fn($value) => $value !== null);
    }
}
