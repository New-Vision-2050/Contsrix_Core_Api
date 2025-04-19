<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Presenters;

use Modules\Company\CompanyCore\Models\Company;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;

class CompanyPresenter extends AbstractPresenter
{
    private Company $company;

    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    public function appendDateToAddress($address)
    {
        $address->country_name = $address->country?->name;
        $address->state_name = $address->state?->name;
        $address->city_name = $address->city?->name;
        unset($address->country);
        unset($address->state);
        unset($address->city);
        return $address;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->company->id,
            'name' => $this?->company?->name,
            'name_ar' => $this?->company->getTranslation("name", "ar"),
            'name_en' => $this?->company->getTranslation("name", "en"),
            'user_name' => $this->company->user_name,
            'email' => $this->company->email,
            'phone' => $this->company->phone,
            'serial_no' => $this->company?->serial_no,
            'country_id' => $this->company->country_id,
            'country_name' => $this->company->country->name,
            'company_type_id' => $this->company->company_type_id,
            'company_field_id' => $this->company->company_field_id,
            'registration_type_id' => $this->company->registration_type_id,
            'general_manager_id' => $this->company->general_manager_id,
            'registration_no' => $this->company?->registration_no,
            'general_manager' => [
                "name" => $this->company->generalManager?->name,
                "email" => $this->company->generalManager?->email,
                "phone" => $this->company->generalManager?->phone,
                "nationality" => $this->company->generalManager?->companyUser?->country?->name
            ],
            'company_type' => $this->company->companyType?->name,
            'company_field' => $this->company->companyField?->name,
            'registration_type' => $this->company->companyRegistrationType?->name,
            "logo" => $this->company->getFirstMedia("logo")?->getFullUrl(),
            'is_active' => $this->company->is_active,
            'complete_data' => $this->company->complete_data,
            'date_activate' => $this->company->date_activate,
            "is_central_company" => $this->company->is_central_company,
            "branch" => request("branch_id") ? $this->company->branches->where("id", request("branch_id"))->first()?->name : $this->company->mainBranch?->name,

            "main_branch" => [
                "name" => $this->company->mainBranch?->name
            ],
            "company_legal_data" => CompanyLegalDataPresenter::collection($this->company->companyLegalData),
            "company_address" => $this->appendDateToAddress($this->company->companyAddress),
            "company_official_documents" => CompanyOfficialDocumentPresenter::collection($this->company->companyOfficialDocuments),
            "branches" => ManagementHierarchyPresenter::collection($this->company->branches),
        ];
    }
}
