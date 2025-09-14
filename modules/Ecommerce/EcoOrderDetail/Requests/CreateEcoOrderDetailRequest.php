<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrderDetail\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoOrderDetail\DTO\CreateEcoOrderDetailDTO;

class CreateEcoOrderDetailRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateEcoOrderDetailDTO(): CreateEcoOrderDetailDTO
    {
        return new CreateEcoOrderDetailDTO(
            name: $this->get('name'),
        );
    }
}
