<?php

declare(strict_types=1);

namespace Modules\Shared\Period\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\Period\DTO\CreatePeriodDTO;

class CreatePeriodRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreatePeriodDTO(): CreatePeriodDTO
    {
        return new CreatePeriodDTO(
            name: $this->get('name'),
        );
    }
}
