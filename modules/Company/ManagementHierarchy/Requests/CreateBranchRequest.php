<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\ManagementHierarchy\DTO\CreateBranchDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementHierarchyDTO;
use Ramsey\Uuid\Uuid;
use Illuminate\Validation\Rule;

class CreateBranchRequest extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'parent_id' => 'required|exists:management_hierarchies,id',
            'manager_id' => 'required|exists:users,id',
            "phone" => "required|phone",
            "email" => "required|email",
            'latitude' => [
                'nullable',
                'numeric',
                Rule::requiredIf(function () {
                    return empty($this->input('country_id')) ||
                        empty($this->input('state_id')) ||
                        empty($this->input('city_id'));
                }),
            ],
            'longitude' => [
                'nullable',
                'numeric',
                Rule::requiredIf(function () {
                    return empty($this->input('country_id')) ||
                        empty($this->input('state_id')) ||
                        empty($this->input('city_id'));
                }),
            ],
            "country_id" => "required|exists:countries,id",
            "state_id" => "required|exists:states,id",
            "city_id" => "required|exists:cities,id",

            'default_constraint_id' => [
                'nullable',
                'uuid',
                Rule::exists('attendance_constraints', 'id')->where(function ($query) {
                    $query->where('company_id', $this->declareCompanyAndBranchUsingRequest()[0]->id);
                }),
            ],

        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('validation.branch.name_required'),
            'name.string' => __('validation.branch.name_string'),

            'parent_id.required' => __('validation.branch.parent_id_required'),
            'parent_id.exists' => __('validation.branch.parent_id_exists'),

            'manager_id.required' => __('validation.branch.manager_id_required'),
            'manager_id.exists' => __('validation.branch.manager_id_exists'),

            'phone.required' => __('validation.branch.phone_required'),
            'phone.phone' => __('validation.branch.phone_invalid'),

            'email.required' => __('validation.branch.email_required'),
            'email.email' => __('validation.branch.email_invalid'),

            'latitude.required' => __('validation.branch.latitude_required'),
            'latitude.numeric' => __('validation.branch.latitude_numeric'),

            'longitude.required' => __('validation.branch.longitude_required'),
            'longitude.numeric' => __('validation.branch.longitude_numeric'),

            'country_id.required' => __('validation.branch.country_required'),
            'country_id.exists' => __('validation.branch.country_exists'),

            'state_id.required' => __('validation.branch.state_required'),
            'state_id.exists' => __('validation.branch.state_exists'),

            'city_id.required' => __('validation.branch.city_required'),
            'city_id.exists' => __('validation.branch.city_exists'),
        ];
    }

    public function createCreateBranchDTO(): CreateBranchDTO
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();

        return new CreateBranchDTO(
            name: $this->get('name'),
            companyId: Uuid::fromString($company->id),
            parentId: $this->get('parent_id') !== null ? (int) $this->get('parent_id') : $this->get("parent_id"),
            managerId: Uuid::fromString($this->get('manager_id')),
            phone: $this->get('phone'),
            email: $this->get('email'),
            latitude: $this->get('latitude'),
            longitude: $this->get('longitude'),
            countryId: (string)$this->get('country_id'),
            stateId: (string)$this->get('state_id'),
            cityId: (string)$this->get('city_id'),
            defaultConstraintId: $this->get('default_constraint_id')

        );
    }
}
