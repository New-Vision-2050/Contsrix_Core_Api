<?php

declare(strict_types=1);

namespace Modules\Shared\TypeWorkingHour\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\TypeWorkingHour\DTO\CreateTypeWorkingHourDTO;

class CreateTypeWorkingHourRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateTypeWorkingHourDTO(): CreateTypeWorkingHourDTO
    {
        return new CreateTypeWorkingHourDTO(
            name: $this->get('name'),
        );
    }
}
