<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\RoleAndPermission\DTO\RoleWidgetsDataDTO;

class RoleWidgetsPresenter extends AbstractPresenter
{
    public function __construct(private readonly RoleWidgetsDataDTO $dto)
    {
    }

    protected function present(bool $isListing = false): ?array
    {
        $totalRoles = $this->dto->total_roles;

        $calculatePercentage = function (int $value) use ($totalRoles): float {
            if ($totalRoles === 0) {
                return 0;
            }
            return round(($value / $totalRoles) * 100, 0);
        };

        return [
            [
                'name' => 'اجمالي عدد الادوار',
                'number' => $this->dto->total_roles,
                'percentage' => 100,
            ],
            [
                'name' => 'اجمالي الادوار الرئيسية',
                'number' => $this->dto->main_roles,
                'percentage' => $calculatePercentage($this->dto->main_roles),
            ],
            [
                'name' => 'اجمالي الادوار الفعالة',
                'number' => $this->dto->active_roles,
                'percentage' => $calculatePercentage($this->dto->active_roles),
            ],
            [
                'name' => 'اجمالي الادوار غير الفعالة',
                'number' => $this->dto->inactive_roles,
                'percentage' => $calculatePercentage($this->dto->inactive_roles),
            ],
        ];
    }
}
