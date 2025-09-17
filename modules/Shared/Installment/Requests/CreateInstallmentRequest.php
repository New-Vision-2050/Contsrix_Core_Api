<?php

declare(strict_types=1);

namespace Modules\Shared\Installment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\Installment\DTO\CreateInstallmentDTO;

class CreateInstallmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateInstallmentDTO(): CreateInstallmentDTO
    {
        return new CreateInstallmentDTO(
            name: $this->get('name'),
        );
    }
}
