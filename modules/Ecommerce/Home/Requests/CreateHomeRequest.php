<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Home\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\Home\DTO\CreateHomeDTO;

class CreateHomeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateHomeDTO(): CreateHomeDTO
    {
        return new CreateHomeDTO(
            name: $this->get('name'),
        );
    }
}
