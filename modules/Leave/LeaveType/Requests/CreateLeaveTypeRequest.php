<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Leave\LeaveType\DTO\CreateLeaveTypeDTO;

class CreateLeaveTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:leave_types,name,NULL,id,company_id,' . tenant('id'),
            'is_payed' => 'sometimes|boolean',
            'is_deduct_from_balance' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('leave.leave_type.name.required'),
            'name.string' => __('leave.leave_type.name.string'),
            'name.max' => __('leave.leave_type.name.max'),
            'is_payed.boolean' => __('leave.leave_type.is_payed.boolean'),
            'is_deduct_from_balance.boolean' => __('leave.leave_type.is_deduct_from_balance.boolean'),
        ];
    }

    public function createCreateLeaveTypeDTO(): CreateLeaveTypeDTO
    {
        return new CreateLeaveTypeDTO(
            name: $this->get('name'),
            is_payed: (bool) $this->get('is_payed', false),
            is_deduct_from_balance: (bool) $this->get('is_deduct_from_balance', false),
        );
    }
}
