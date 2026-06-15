<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\DTO;

final class UpdateInternalProcessTypeDTO
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $name = null,
        public readonly ?string $form = null,
        public readonly ?array $conditions = null,
        public readonly ?array $ordering = null,
        public readonly ?bool $isActive = null,
        public readonly ?int $sortOrder = null,
    ) {}
}
