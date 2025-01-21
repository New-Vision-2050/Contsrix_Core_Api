<?php

declare(strict_types=1);

namespace Modules\Company\Presenters;

use Modules\Company\Models\Company;
use BasePackage\Shared\Presenters\AbstractPresenter;

class CompanyPresenter extends AbstractPresenter
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
            'name' => $this->company->name,
            'email' => $this->company->email,
            'phone' => $this->company->email,
        ];
    }
}
