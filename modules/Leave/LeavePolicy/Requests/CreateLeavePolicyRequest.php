<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Leave\LeavePolicy\DTO\CreateLeavePolicyDTO;
use Modules\Leave\LeaveType\Models\LeaveType;

class CreateLeavePolicyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:leave_policies,name,NULL,id,company_id,' . tenant('id'),
            'leave_type_id' => 'required|uuid|exists:leave_types,id,company_id,' . tenant('id'),
            'leave_category_id' => 'required|uuid|exists:leave_policy_categories,id,company_id,' . tenant('id'),
            'max_balance_days' => 'nullable|integer|min:0',

            // With-balance fields (required when the selected leave type has is_deduct_from_balance = true)
            'total_days' => 'nullable|integer|min:0',
            'min_notice_days' => 'nullable|integer|min:0',
            'day_calculation_type' => 'nullable|string|in:work_day,calendar',
            'entitlement_type' => 'nullable|string|in:upfront,monthly',
            'entitlement_start_from' => 'nullable|string|in:hire_date',
            'promotion_type' => 'nullable|string|in:after_duration',
            'promotion_duration_months' => 'nullable|integer|min:0|required_if:promotion_type,after_duration',
            'is_rollover_allowed' => 'sometimes|boolean',
            'rollover_duration_months' => 'nullable|integer|min:0',
            'allow_exceed_balance' => 'sometimes|boolean',

            // Without-balance field (required when the selected leave type has is_deduct_from_balance = false)
            'max_days' => 'nullable|integer|min:0',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $leaveTypeId = $this->get('leave_type_id');

            if (!$leaveTypeId || !Uuid::isValid($leaveTypeId)) {
                return;
            }

            $leaveType = LeaveType::find($leaveTypeId);

            if (!$leaveType) {
                return;
            }

            if ($leaveType->is_deduct_from_balance) {
                if ($this->get('total_days') === null) {
                    $validator->errors()->add('total_days', __('leave.leave_policy.total_days.required'));
                }
                if ($this->get('day_calculation_type') === null) {
                    $validator->errors()->add('day_calculation_type', __('leave.leave_policy.day_calculation_type.required'));
                }
                if ($this->get('entitlement_type') === null) {
                    $validator->errors()->add('entitlement_type', __('leave.leave_policy.entitlement_type.required'));
                }
            } else {
                if ($this->get('max_days') === null) {
                    $validator->errors()->add('max_days', __('leave.leave_policy.max_days.required'));
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => __('leave.leave_policy.name.required'),
            'name.string' => __('leave.leave_policy.name.string'),
            'name.max' => __('leave.leave_policy.name.max'),
            'leave_type_id.required' => __('leave.leave_policy.leave_type_id.required'),
            'leave_type_id.uuid' => __('leave.leave_policy.leave_type_id.uuid'),
            'leave_type_id.exists' => __('leave.leave_policy.leave_type_id.exists'),
            'leave_category_id.required' => __('leave.leave_policy.leave_category_id.required'),
            'leave_category_id.uuid' => __('leave.leave_policy.leave_category_id.uuid'),
            'leave_category_id.exists' => __('leave.leave_policy.leave_category_id.exists'),
            'max_balance_days.integer' => __('leave.leave_policy.max_balance_days.integer'),
            'max_balance_days.min' => __('leave.leave_policy.max_balance_days.min'),
            'total_days.integer' => __('leave.leave_policy.total_days.integer'),
            'total_days.min' => __('leave.leave_policy.total_days.min'),
            'min_notice_days.integer' => __('leave.leave_policy.min_notice_days.integer'),
            'min_notice_days.min' => __('leave.leave_policy.min_notice_days.min'),
            'day_calculation_type.in' => __('leave.leave_policy.day_calculation_type.in'),
            'entitlement_type.in' => __('leave.leave_policy.entitlement_type.in'),
            'entitlement_start_from.in' => __('leave.leave_policy.entitlement_start_from.in'),
            'promotion_type.in' => __('leave.leave_policy.promotion_type.in'),
            'promotion_duration_months.integer' => __('leave.leave_policy.promotion_duration_months.integer'),
            'promotion_duration_months.required_if' => __('leave.leave_policy.promotion_duration_months.required_if'),
            'is_rollover_allowed.boolean' => __('leave.leave_policy.is_rollover_allowed.boolean'),
            'rollover_duration_months.integer' => __('leave.leave_policy.rollover_duration_months.integer'),
            'allow_exceed_balance.boolean' => __('leave.leave_policy.allow_exceed_balance.boolean'),
            'max_days.integer' => __('leave.leave_policy.max_days.integer'),
            'max_days.min' => __('leave.leave_policy.max_days.min'),
        ];
    }

    public function createCreateLeavePolicyDTO(): CreateLeavePolicyDTO
    {
        return new CreateLeavePolicyDTO(
            name: $this->get('name'),
            leave_type_id: Uuid::fromString($this->get('leave_type_id')),
            leave_category_id: Uuid::fromString($this->get('leave_category_id')),
            max_balance_days: $this->get('max_balance_days'),
            total_days: $this->get('total_days'),
            min_notice_days: $this->get('min_notice_days'),
            day_calculation_type: $this->get('day_calculation_type'),
            entitlement_type: $this->get('entitlement_type'),
            entitlement_start_from: $this->get('entitlement_start_from'),
            promotion_type: $this->get('promotion_type'),
            promotion_duration_months: $this->get('promotion_duration_months'),
            is_rollover_allowed: (bool) $this->get('is_rollover_allowed', false),
            rollover_duration_months: $this->get('rollover_duration_months'),
            allow_exceed_balance: (bool) $this->get('allow_exceed_balance', false),
            max_days: $this->get('max_days'),
        );
    }
}
