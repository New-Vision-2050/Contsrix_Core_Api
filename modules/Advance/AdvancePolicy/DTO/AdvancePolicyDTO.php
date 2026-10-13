<?php

declare(strict_types=1);

namespace Modules\Advance\AdvancePolicy\DTO;

class AdvancePolicyDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $max_amount,
        public readonly int $max_installments,
        public readonly int $max_active_advances,
        public readonly bool $requires_attachment,
    ) {}
}
