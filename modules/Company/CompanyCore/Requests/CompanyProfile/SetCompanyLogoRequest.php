<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\DTO\CompanyProfile\AssignLogoToCompanyDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateOfficialCompanyDataRequestDTO;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Ramsey\Uuid\Uuid;

class SetCompanyLogoRequest extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function rules(): array
    {
        return [
            'logo' => 'required|image|mimes:jpeg,jpg,png,gif,svg|max:5000'
            ];
    }

    public function createAssignLogoToCompanyDTO(): AssignLogoToCompanyDTO
    {
        [ $company , $branch]= $this->declareCompanyAndBranchUsingRequest();

        return new AssignLogoToCompanyDTO(
            managementHierarchy: $branch,
            logo: $this->file("logo"),
        );
    }
}

