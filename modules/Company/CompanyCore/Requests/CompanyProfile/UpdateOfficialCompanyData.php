<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateOfficialCompanyDataCommand;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Ramsey\Uuid\Uuid;
use Modules\Company\CompanyCore\Commands\UpdateCompanyCommand;
use App\Rules\Company\CompanyCore\Rules\RegistrationNoRule;
class UpdateOfficialCompanyData extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;
    public function rules(): array
    {
        return [
            'name_en' => 'required|string',
            'email' => 'required|email|string|unique:companies,email,' . Uuid::fromString(tenant("id")),
            'phone' => 'required|string',
            'branch_name' => 'required|string',
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
        );
    }
}

