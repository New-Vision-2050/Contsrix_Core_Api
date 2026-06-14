<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\DTO;

final class UpdateInternalProcessTypeDTO
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $name = null,
        public readonly ?array $settings = null,
        public readonly ?bool $isActive = null,
        public readonly ?int $sortOrder = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'name'       => $this->name,
            'settings'   => $this->settings,
            'is_active'  => $this->isActive,
            'sort_order' => $this->sortOrder,
        ], static fn ($v) => $v !== null);
    }
}
