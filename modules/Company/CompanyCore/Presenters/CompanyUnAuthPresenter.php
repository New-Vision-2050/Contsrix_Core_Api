<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Presenters;

use Modules\Company\CompanyCore\Models\Company;
use BasePackage\Shared\Presenters\AbstractPresenter;

class CompanyUnAuthPresenter extends AbstractPresenter
{
    private Company $company;

    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->company->id,
            'name' => $this?->company?->name,
            'user_name' => $this->company->user_name,
            "logo"=> $this->company->getFirstMedia("logo")?->getFullUrl(),


        ];
    }
}
