<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateOfficialCompanyDataCommand;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Ramsey\Uuid\Uuid;
use Modules\Company\CompanyCore\Commands\UpdateCompanyCommand;
use App\Rules\Company\CompanyCore\Rules\RegistrationNoRule;
use App\Rules\Company\CompanyCore\Rules\CentralCompanyPackageUpdateRule;
class UpdateOfficialCompanyData extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;
    public function rules(): array
    {
        // Get current company to check if it's central
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();
        
        return [
            'name_en' => 'required|string',
            'email' => 'required|email|string',//|unique:companies,email,' . Uuid::fromString(tenant("id")
            'phone' => 'required|string',
            'branch_name' => 'required|string',
            'company_type_id' => 'required|exists:company_types,id',
            'packages' => $company->is_central_company 
                ? ['sometimes', 'array', new CentralCompanyPackageUpdateRule()]
                : ['required', 'array', new CentralCompanyPackageUpdateRule()],
            "packages.*" => 'required_with:packages|exists:packages,id',
        ];
    }
    public function messages(): array
    {
        return [
            'name_en.required' => __('validation.company_official.name_required'),
            'email.required' => __('validation.company_official.email_required'),
            'email.email' => __('validation.company_official.email_valid'),
            'phone.required' => __('validation.company_official.phone_required'),
            'branch_name.required' => __('validation.company_official.branch_required'),
            'company_type_id.required' => __('validation.company_official.company_type_required'),
            'company_type_id.exists' => __('validation.company_official.company_type_exists'),
        ];
    }
    public function createUpdateOfficialCompanyDataCommand(): UpdateOfficialCompanyDataCommand
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();
        return new UpdateOfficialCompanyDataCommand(

            id: Uuid::fromString($company->id),
            nameEn: $this->get('name_en'),
            email: $this->get('email'),
            phone: $this->get('phone'),
            branchName: $this->get('branch_name'),
            companyTypeId: $this->get('company_type_id'),
            branch : $branch,
            packages : $this->get('packages'),
        );
    }
}

