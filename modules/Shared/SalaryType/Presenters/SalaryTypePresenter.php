<?php

declare(strict_types=1);

namespace Modules\Shared\SalaryType\Presenters;

use Modules\Shared\SalaryType\Models\SalaryType;
use BasePackage\Shared\Presenters\AbstractPresenter;

class SalaryTypePresenter extends AbstractPresenter
{
    private SalaryType $salaryType;

    public function __construct(SalaryType $salaryType)
    {
        $this->salaryType = $salaryType;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->salaryType->id,
            'name' => $this->salaryType->name,
            'code' => $this->salaryType->code,
        ];
    }
}
