<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicyCategory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Leave\LeavePolicyCategory\DTO\CreateLeavePolicyCategoryDTO;

class CreateLeavePolicyCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:leave_policy_categories,name,NULL,id,company_id,' . tenant('id'),
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('leave.leave_policy_category.name.required'),
            'name.string' => __('leave.leave_policy_category.name.string'),
            'name.max' => __('leave.leave_policy_category.name.max'),
            'name.unique' => __('leave.leave_policy_category.name.unique'),
        ];
    }

    public function createCreateLeavePolicyCategoryDTO(): CreateLeavePolicyCategoryDTO
    {
        return new CreateLeavePolicyCategoryDTO(
            name: $this->get('name'),
        );
    }
}
