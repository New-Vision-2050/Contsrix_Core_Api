<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\DTO;

use BasePackage\Shared\DataTransferObjects\BaseDTO;

class PermissionWidgetsDataDTO
{
    public function __construct(
        public readonly int $total_permissions,
        public readonly int $total_main_permissions,
        public readonly int $active_permissions,
        public readonly int $inactive_permissions,
    ) {
    }

    public static function fromArray(array $data)
    {
        return new self(
            total_permissions: $data['total_permissions'] ?? 0,
            total_main_permissions: $data['total_main_permissions'] ?? 0,
            active_permissions: $data['active_permissions'] ?? 0,
            inactive_permissions: $data['inactive_permissions'] ?? 0,
        );
    }
}
