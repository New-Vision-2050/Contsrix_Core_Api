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
        ];
    }

    public function toDTO(): GetInfoAlertDTO
    {
        return new GetInfoAlertDTO(
            userId: $this->get('user_id'),
        );
    }
}
