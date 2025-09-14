<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Leave\LeavePolicy\Commands\UpdateLeavePolicyCommand;
use Modules\Leave\LeavePolicy\Handlers\UpdateLeavePolicyHandler;
use Modules\Leave\LeavePolicy\Rules\AnnualYearProtectionRules;

class UpdateLeavePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:leave_policies,name,' . $this->route('id') . ',id,company_id,' . tenant('id'),
            'total_days' => 'nullable|integer|min:0',
            'day_type' => 'nullable|string|max:100',
            'is_rollover_allowed' => 'sometimes|boolean',
            'max_days_per_request' => 'nullable|integer|min:0',
            'upgrade_condition' => 'nullable|string|max:500',
            'is_allow_half_day' => 'sometimes|boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $id = Uuid::fromString($this->route('id'));
            $protectionErrors = AnnualYearProtectionRules::validateUpdateFields($id, $this->all());

            foreach ($protectionErrors as $field => $message) {
                $validator->errors()->add($field, $message);
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => __('leave.leave_policy.name.required'),
            'name.string' => __('leave.leave_policy.name.string'),
            'name.max' => __('leave.leave_policy.name.max'),
            'total_days.integer' => __('leave.leave_policy.total_days.integer'),
            'total_days.min' => __('leave.leave_policy.total_days.min'),
            'day_type.string' => __('leave.leave_policy.day_type.string'),
            'day_type.max' => __('leave.leave_policy.day_type.max'),
            'is_rollover_allowed.boolean' => __('leave.leave_policy.is_rollover_allowed.boolean'),
            'max_days_per_request.integer' => __('leave.leave_policy.max_days_per_request.integer'),
            'max_days_per_request.min' => __('leave.leave_policy.max_days_per_request.min'),
            'upgrade_condition.string' => __('leave.leave_policy.upgrade_condition.string'),
            'is_allow_half_day.boolean' => __('leave.leave_policy.is_allow_half_day.boolean'),
        ];
    }

    public function createUpdateLeavePolicyCommand(): UpdateLeavePolicyCommand
    {
        return new UpdateLeavePolicyCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            total_days: $this->get('total_days'),
            day_type: $this->get('day_type'),
            is_rollover_allowed: (bool) $this->get('is_rollover_allowed', false),
            max_days_per_request: $this->get('max_days_per_request'),
            upgrade_condition: $this->get('upgrade_condition'),
            is_allow_half_day: (bool) $this->get('is_allow_half_day', false),
        );
    }
}
