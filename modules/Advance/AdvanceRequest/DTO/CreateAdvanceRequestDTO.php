<?php

declare(strict_types=1);

namespace Modules\Advance\AdvanceRequest\DTO;

class CreateAdvanceRequestDTO
{
    public function __construct(
        public readonly string $advance_policy_id,
        public readonly float $amount,
        public readonly int $installments,
        public readonly string $reason,
        public readonly ?array $attachments,
        public readonly ?string $notes,
    ) {}
}
