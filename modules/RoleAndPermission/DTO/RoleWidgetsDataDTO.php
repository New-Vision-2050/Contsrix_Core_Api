<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\DTO;

use BasePackage\Shared\DataTransferObjects\BaseDTO;

class RoleWidgetsDataDTO
{
    public function __construct(
        public readonly int $total_roles,
        public readonly int $main_roles,
        public readonly int $active_roles,
        public readonly int $inactive_roles,
    ) {
    }

    public static function fromArray(array $data)
    {
        return new self(
            total_roles: $data['total_roles'] ?? 0,
            main_roles: $data['main_roles'] ?? 0,
            active_roles: $data['active_roles'] ?? 0,
            inactive_roles: $data['inactive_roles'] ?? 0,
        );
    }
}
