<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicyCategory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Leave\LeavePolicyCategory\Commands\UpdateLeavePolicyCategoryCommand;

class UpdateLeavePolicyCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:leave_policy_categories,name,' . $this->route('id') . ',id,company_id,' . tenant('id'),
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

    public function createUpdateLeavePolicyCategoryCommand(): UpdateLeavePolicyCategoryCommand
    {
        return new UpdateLeavePolicyCategoryCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
