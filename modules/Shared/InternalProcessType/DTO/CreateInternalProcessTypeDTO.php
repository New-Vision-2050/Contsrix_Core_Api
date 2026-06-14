<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\DTO;

use Modules\Shared\InternalProcessType\Support\InternalProcessTypePayload;

final class CreateInternalProcessTypeDTO
{
    public function __construct(
        public readonly string $entityType,
        public readonly string $name,
        public readonly string $form,
        public readonly array $conditions,
        public readonly array $ordering = [],
        public readonly bool $isActive = true,
        public readonly int $sortOrder = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'company_id'  => tenant('id'),
            'entity_type' => $this->entityType,
            'name'        => $this->name,
            'settings'    => InternalProcessTypePayload::pack(
                $this->form,
                $this->conditions,
                $this->ordering,
            ),
            'is_active'   => $this->isActive,
            'sort_order'  => $this->sortOrder,
        ];
    }
}
