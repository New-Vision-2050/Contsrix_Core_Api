<?php

declare(strict_types=1);

namespace Modules\Company\CompanyType\Presenters;

use Modules\Company\CompanyType\Models\CompanyType;
use BasePackage\Shared\Presenters\AbstractPresenter;

class CompanyTypePresenter extends AbstractPresenter
{
    private CompanyType $companyType;

    public function __construct(CompanyType $companyType)
    {
        $this->companyType = $companyType;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->companyType->id,
            'name' => $this->companyType->name,
        ];
    }
}
