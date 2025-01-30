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
            'country_id' => $this->company->country_id,
            'company_type_id' => $this->company->company_type_id,
            'company_field_id' => $this->company->company_field_id,
            'registration_type_id' => $this->company->registration_type_id,
            'general_manager_id' => $this->company->general_manager_id,
            'registration_no' => $this->company->companyRegistrationForm->registration_no,
            'general_manager_name' => $this->company->generalManager->name,
            'company_type' => $this->company->companyType->name,
            'company_field' => $this->company->companyField->name,
            'registration_type' => $this->company->companyRegistrationType->name,
        ];
    }
}
