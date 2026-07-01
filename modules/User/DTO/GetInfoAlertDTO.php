<?php

declare(strict_types=1);

namespace Modules\User\DTO;

class GetInfoAlertDTO
{
    public function __construct(
        public readonly ?string $userId = null,
        public readonly ?string $type = null,
        public readonly ?string $branchId = null,
        public readonly ?string $search = null,
    ) {}
}
