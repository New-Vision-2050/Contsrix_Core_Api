<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\ManagementHierarchy\Commands\UpdateBranchCommand;
use Modules\Company\ManagementHierarchy\DTO\CreateBranchDTO;
use Ramsey\Uuid\Uuid;
use Modules\Company\ManagementHierarchy\Commands\UpdateManagementHierarchyCommand;
use Modules\Company\ManagementHierarchy\Handlers\UpdateManagementHierarchyHandler;

class UpdateBranchRequest extends FormRequest
{

    use PreDeclareComapnyAndBranchDependOnReqeuest;
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'parent_id' => 'nullable|exists:management_hierarchies,id',
            'manager_id' => 'required|exists:users,id',
            "phone" => "required|phone",
            "email" => "required|email",
            "latitude" => "required|numeric",
            "longitude" => "required|numeric",
            "country_id" => "required|exists:countries,id",
            "state_id" => "required|exists:states,id",
            "city_id" => "required|exists:cities,id",
        ];
    }

    public function createUpdateBranchCommand(): UpdateBranchCommand
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();

        return new UpdateBranchCommand(
            id: (int)$this->route('id'),
            name: $this->get('name'),
            companyId: Uuid::fromString($company->id),
            parentId: $this->get('parent_id') !== null ?(int) $this->get('parent_id') : $this->get("parent_id"),
            managerId: Uuid::fromString($this->get('manager_id')),
            phone: $this->get('phone'),
            email: $this->get('email'),
            latitude: $this->get('latitude'),
            longitude: $this->get('longitude'),
            countryId: (string)$this->get('country_id'),
            stateId: (string)$this->get('state_id'),
            cityId: (string)$this->get('city_id'),
        );
    }
}
