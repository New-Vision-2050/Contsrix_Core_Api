<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Presenters;

use Modules\Company\CompanyCore\Models\Company;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\CompanyField\Presenters\CompanyFieldPresenter;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchySimpleDataPresenter;
use Modules\Subscription\CompanyAccessProgram\Presenters\CompanyAccessProgramSimplePresenter;
use Modules\Subscription\Package\Models\Package;
use Modules\Subscription\Package\Presenters\PackageSimplePresenter;

class CompanyPresenter extends AbstractPresenter
{
    private Company $company;

    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    public function appendDateToAddress($address = null)
    {
        if($address ==null) return $address;

        $address->country_name = $address?->country?->name;
        $address->state_name = $address?->state?->name;
        $address->city_name = $address?->city?->name;
        $address->country_lat = $address?->country?->latitude;
        $address->country_long = $address?->country?->longitude;
        $address->country_iso2 = $address?->country?->iso2;
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

            'owner_id' => $this->company->owner?->id,
            'owner_name' => $this->company->owner?->name,

            'email' => request("branch_id") ? $this->company->branches->where("id", request("branch_id"))->first()?->email : $this->company->mainBranch?->email,
            'phone' => request("branch_id") ? $this->company->branches->where("id", request("branch_id"))->first()?->phone : $this->company->mainBranch?->phone,

            'serial_no' => $this->company?->serial_no,
            'country_id' => $this->company?->companyAddress?->country_id,
            'country_name' =>$this->company?->companyAddress?->country?->name,
            'country_lat' => $this->company?->companyAddress?->country?->latitude,
            'country_long' =>$this->company?->companyAddress?->country?->longitude,
            'country_iso2' => $this->company?->companyAddress?->country?->iso2,
            'company_type_id' => $this->company->company_type_id,
            'registration_type_id' => $this->company->registration_type_id,
            'company_field_id' => $this->company->companyFields()->first()?->id,
            'general_manager_id' => $this->company->general_manager_id,
            'registration_no' => $this->company?->registration_no,
            'general_manager' => [
                "name" => $this->company->generalManager?->name,
                "email" => $this->company->generalManager?->email,
                "phone" => $this->company->generalManager?->phone,
                "nationality" => $this->company->generalManager?->companyUser?->country?->name
            ],
            'company_type' => $this->company->companyType?->name,
            'company_field' => $this->company->companyFields ? CompanyFieldPresenter::collection($this->company->companyFields) : [],
            'registration_type' => $this->company->companyRegistrationType?->name,
            "logo" => $this->company->getFirstMedia("logo")?->getFullUrl(),
            'is_active' => $this->company->is_active,
            'complete_data' => $this->company->complete_data,
            'is_draft' => (bool) $this->company->is_draft,
            'date_activate' => $this->company->date_activate,
            "is_central_company" => $this->company->is_central_company,
            "branch" => request("branch_id") ? $this->company->branches->where("id", request("branch_id"))->first()?->name : $this->company->mainBranch?->name,

            "main_branch" => [
                "name" => $this->company->mainBranch?->name
            ],
            "packages" =>PackageSimplePresenter::collection($this->company->packages),
            "company_access_programs" =>CompanyAccessProgramSimplePresenter::collection($this->company->distinctCompanyAccessPrograms),


            // These data points are now available through separate API endpoints
            // Access via: /api/companies/company-profile/company-legal-data
            // "company_legal_data" => CompanyLegalDataPresenter::collection($this->company->companyLegalData),

            // Access via: /api/companies/company-profile/company-address
            // "company_address" => $this->appendDateToAddress($this->company?->companyAddress),

            // Access via: /api/companies/company-profile/company-official-documents
            // "company_official_documents" => CompanyOfficialDocumentPresenter::collection($this->company->companyOfficialDocuments),

            // Access via: /api/companies/company-profile/company-branches
             "branches" => ManagementHierarchySimpleDataPresenter::collection($this->company->branches),

            "created_at" => $this->company->created_at,
        ];
    }
}
