<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoInstallment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoInstallment\DTO\CreateEcoInstallmentDTO;

class CreateEcoInstallmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateEcoInstallmentDTO(): CreateEcoInstallmentDTO
    {
        return new CreateEcoInstallmentDTO(
            name: $this->get('name'),
        );
    }
}
