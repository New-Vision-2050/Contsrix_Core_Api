<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Leave\LeavePolicy\DTO\UpdateHalfDayAllowedDTO;
use Ramsey\Uuid\Uuid;

class UpdateHalfDayAllowedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_allow_half_day' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'is_allow_half_day.required' => 'The allow half day field is required.',
            'is_allow_half_day.boolean' => 'The allow half day field must be true or false.',
        ];
    }

    public function createUpdateHalfDayAllowedDTO(): UpdateHalfDayAllowedDTO
    {
        return new UpdateHalfDayAllowedDTO(
            leavePolicyId: Uuid::fromString($this->route('id')),
            isAllowHalfDay: (bool) $this->get('is_allow_half_day')
        );
    }

    public function getIsAllowHalfDay(): bool
    {
        return (bool) $this->get('is_allow_half_day');
    }
}
