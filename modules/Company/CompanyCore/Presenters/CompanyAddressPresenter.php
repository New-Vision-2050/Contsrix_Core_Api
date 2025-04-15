<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Presenters;

use Modules\Company\CompanyCore\Models\Company;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\CompanyCore\Models\CompanyAddress;
use Modules\Company\CompanyCore\Models\CompanyLegalData;

class CompanyAddressPresenter extends AbstractPresenter
{
    private CompanyAddress $company;

    public function __construct(CompanyAddress $company)
    {
        $this->company = $company;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->company->id,

        ];
    }
}
