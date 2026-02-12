<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\User\DTO\GetInfoAlertDTO;

class GetInfoAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|uuid|exists:users,id',
            'type' => 'nullable|string|in:work_permit,passport,identity,border_number,entry_number,qualification,bank_account',
            'branch_id' => 'nullable|exists:management_hierarchies,id',
        ];
    }

    public function toDTO(): GetInfoAlertDTO
    {
        return new GetInfoAlertDTO(
            userId: $this->get('user_id'),
            type: $this->get('type'),
            branchId: $this->get('branch_id'),
        );
    }
}
