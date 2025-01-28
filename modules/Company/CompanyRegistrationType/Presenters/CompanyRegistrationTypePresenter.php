<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationType\Presenters;

use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;
use BasePackage\Shared\Presenters\AbstractPresenter;

class CompanyRegistrationTypePresenter extends AbstractPresenter
{
    private CompanyRegistrationType $companyRegistrationType;

    public function __construct(CompanyRegistrationType $companyRegistrationType)
    {
        $this->companyRegistrationType = $companyRegistrationType;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->companyRegistrationType->id,
            'name' => $this->companyRegistrationType->name,
        ];
    }
}
