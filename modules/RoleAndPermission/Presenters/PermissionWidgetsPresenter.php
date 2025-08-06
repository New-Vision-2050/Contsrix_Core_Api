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
        return [
            [
                'name' => 'إجمالي عدد الصلاحيات',
                'number' => $this->dto->total_permissions,
            ],
            [
                'name' => 'إجمالي عدد الصلاحيات الرئيسية',
                'number' => $this->dto->total_main_permissions,
            ],
            [
                'name' => 'إجمالي الصلاحيات النشطة',
                'number' => $this->dto->active_permissions,
            ],
            [
                'name' => 'إجمالي الصلاحيات غير النشطة',
                'number' => $this->dto->inactive_permissions,
            ],
        ];
    }
}
