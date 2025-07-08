<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use BasePackage\Shared\Presenters\BasePresenter;
use Modules\RoleAndPermission\DTO\RoleWidgetsDataDTO;

class RoleWidgetsPresenter extends AbstractPresenter
{
    public function __construct(private readonly RoleWidgetsDataDTO $dto)
    {
    }


    protected function present(bool $isListing = false): ?array
    {
        return [
            [
                'name' => 'اجمالي عدد الادوار',
                'number' => $this->dto->total_roles,
            ],
            [
                'name' => 'اجمالي الادوار الرئيسية',
                'number' => $this->dto->main_roles,
            ],
            [
                'name' => 'اجمالي الادوار الفعالة',
                'number' => $this->dto->active_roles,
            ],
            [
                'name' => 'اجمالي الادوار غير الفعالة',
                'number' => $this->dto->inactive_roles,
            ],
        ];
    }
}
