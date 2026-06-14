<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\DTO;

final class CreateInternalProcessTypeDTO
{
    public function __construct(
        public readonly string $entityType,
        public readonly string $name,
        public readonly array $settings,
        public readonly bool $isActive = true,
        public readonly int $sortOrder = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'company_id'  => tenant('id'),
            'entity_type' => $this->entityType,
            'name'        => $this->name,
            'settings'    => $this->settings,
            'is_active'   => $this->isActive,
            'sort_order'  => $this->sortOrder,
        ];
    }
}
