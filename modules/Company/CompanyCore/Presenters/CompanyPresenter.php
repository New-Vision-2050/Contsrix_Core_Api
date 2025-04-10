<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Presenters;

use Modules\Company\CompanyCore\Models\Company;
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
            'name' => $this?->company?->name,
            'user_name' => $this->company->user_name,
            'email' => $this->company->email,
            'phone' => $this->company?->phone,
            'serial_no' => $this->company?->serial_no,
            'country_id' => $this->company->country_id,
            'company_type_id' => $this->company->company_type_id,
            'company_field_id' => $this->company?->company_field_id,
            'registration_type_id' => $this->company?->registration_type_id,
            'general_manager_id' => $this->company->general_manager_id,
            'registration_no' => $this->company?->registration_no,
            'general_manager_name' => $this->company?->generalManager?->name,
            'company_type' => $this->company?->companyType?->name,
            'company_field' => $this->company?->companyField?->name,
            'registration_type' => $this->company?->companyRegistrationType?->name,
            "logo"=> $this->company->getFirstMedia("logo")?->getFullUrl(),
            'is_active' => $this->company->is_active,
            'complete_data' => $this->company->complete_data,
            'date_activate' => $this->company->date_activate,
            "is_central_company" => $this->company->is_central_company
        ];
    }
}
