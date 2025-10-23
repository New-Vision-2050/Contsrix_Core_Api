<?php

declare(strict_types=1);

namespace Modules\Unit\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Unit\DTO\CreateUnitDTO;

class CreateUnitRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateUnitDTO(): CreateUnitDTO
    {
        return new CreateUnitDTO(
            name: $this->get('name'),
        );
    }
}
