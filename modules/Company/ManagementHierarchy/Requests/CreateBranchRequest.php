<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\ManagementHierarchy\DTO\CreateBranchDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementHierarchyDTO;
use Ramsey\Uuid\Uuid;

class CreateBranchRequest extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'parent_id' => 'nullable|exists:management_hierarchy,id',
            "phone"=>"required|unique:management_hierarchy,phone",
            "phone_code"=>"required",
            "email"=>"required|unique:management_hierarchy,email",
            "lattitude" => "required|numeric",
            "longitude" => "required|numeric",
            "country_id" => "required|exists:countries,id",
            "state_id" => "required|exists:states,id",
            "city_id" => "required|exists:cities,id",

        ];
    }

    public function createCreateBranchDTO(): CreateBranchDTO
    {
        [$company, $branch] = $this->getCompanyAndBranchDependOnReqeuest();

        return new CreateBranchDTO(
            name: $this->get('name'),
            companyId: Uuid::fromString($company->id),
            parentId: $this->get('parent_id'),
            phone: $this->get('phone'),
            phoneCode: $this->get('phone_code'),
            email: $this->get('email'),
            lattitude: $this->get('lattitude'),
            longitude: $this->get('longitude'),
            countryId: $this->get('country_id'),
            stateId: $this->get('state_id'),
            cityId: $this->get('city_id'),
        );
    }
}
