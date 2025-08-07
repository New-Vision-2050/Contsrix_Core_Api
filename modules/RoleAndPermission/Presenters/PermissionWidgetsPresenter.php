<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\RoleAndPermission\DTO\PermissionWidgetsDataDTO;

class PermissionWidgetsPresenter extends AbstractPresenter
{
    public function __construct(private readonly PermissionWidgetsDataDTO $dto)
    {
    }

    protected function present(bool $isListing = false): ?array
    {
        $totalPermissions = $this->dto->total_permissions;

        $calculatePercentage = function (int $value) use ($totalPermissions): float {
            if ($totalPermissions === 0) {
                return 0.0;
            }
            return round(($value / $totalPermissions) * 100, 2);
        };

        return [
            [
                'name' => 'إجمالي عدد الصلاحيات',
                'number' => $this->dto->total_permissions,
                'percentage' => 100.0,
            ],
            [
                'name' => 'إجمالي عدد الصلاحيات الرئيسية',
                'number' => $this->dto->total_main_permissions,
                'percentage' => $calculatePercentage($this->dto->total_main_permissions),
            ],
            [
                'name' => 'إجمالي الصلاحيات النشطة',
                'number' => $this->dto->active_permissions,
                'percentage' => $calculatePercentage($this->dto->active_permissions),
            ],
            [
                'name' => 'إجمالي الصلاحيات غير النشطة',
                'number' => $this->dto->inactive_permissions,
                'percentage' => $calculatePercentage($this->dto->inactive_permissions),
            ],
        ];
    }
}
