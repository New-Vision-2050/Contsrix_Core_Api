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
            'parent_id' => 'nullable|exists:management_hierarchies,id',
            'manager_id' => 'required|exists:users,id',
            "phone" => "required|unique:management_hierarchies,phone",
            "email" => "required|unique:management_hierarchies,email",
            "lattitude" => "required|numeric",
            "longitude" => "required|numeric",
            "country_id" => "required|exists:countries,id",
            "state_id" => "required|exists:states,id",
            "city_id" => "required|exists:cities,id",

        ];
    }

    public function createCreateBranchDTO(): CreateBranchDTO
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();

        return new CreateBranchDTO(
            name: $this->get('name'),
            companyId: Uuid::fromString($company->id),
            parentId: $this->get('parent_id') !== null ? Uuid::fromString($this->get('parent_id')) : $this->get("parent_id"),
            managerId: Uuid::fromString($this->get('manager_id')),
            phone: $this->get('phone'),
            email: $this->get('email'),
            lattitude: $this->get('lattitude'),
            longitude: $this->get('longitude'),
            countryId: (string)$this->get('country_id'),
            stateId: (string)$this->get('state_id'),
            cityId: (string)$this->get('city_id'),
        );
    }
}
