<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Leave\LeavePolicy\DTO\UpdateRolloverAllowedDTO;
use Ramsey\Uuid\Uuid;

class UpdateRolloverAllowedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_rollover_allowed' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'is_rollover_allowed.required' => 'The rollover allowed field is required.',
            'is_rollover_allowed.boolean' => 'The rollover allowed field must be true or false.',
        ];
    }

    public function createUpdateRolloverAllowedDTO(): UpdateRolloverAllowedDTO
    {
        return new UpdateRolloverAllowedDTO(
            leavePolicyId: Uuid::fromString($this->route('id')),
            isRolloverAllowed: (bool) $this->get('is_rollover_allowed')
        );
    }

    public function getIsRolloverAllowed(): bool
    {
        return (bool) $this->get('is_rollover_allowed');
    }
}
