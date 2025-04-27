<?php

declare(strict_types=1);

namespace Modules\Shared\TimeUnit\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\TimeUnit\DTO\CreateTimeUnitDTO;

class CreateTimeUnitRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateTimeUnitDTO(): CreateTimeUnitDTO
    {
        return new CreateTimeUnitDTO(
            name: $this->get('name'),
        );
    }
}
