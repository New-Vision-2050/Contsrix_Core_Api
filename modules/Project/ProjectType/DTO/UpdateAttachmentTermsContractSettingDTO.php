<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\DTO;

class UpdateAttachmentTermsContractSettingDTO
{
    public function __construct(
        public readonly ?int $is_name = null,
        public readonly ?int $is_type = null,
        public readonly ?int $is_size = null,
        public readonly ?int $is_creator = null,
        public readonly ?int $is_create_date = null,
        public readonly ?int $is_downloadable = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'is_name' => $this->is_name,
            'is_type' => $this->is_type,
            'is_size' => $this->is_size,
            'is_creator' => $this->is_creator,
            'is_create_date' => $this->is_create_date,
            'is_downloadable' => $this->is_downloadable,
        ], fn($value) => $value !== null);
    }
}
