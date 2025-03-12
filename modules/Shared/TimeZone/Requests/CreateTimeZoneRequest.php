<?php

declare(strict_types=1);

namespace Modules\Shared\TimeZone\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\TimeZone\DTO\CreateTimeZoneDTO;

class CreateTimeZoneRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateTimeZoneDTO(): CreateTimeZoneDTO
    {
        return new CreateTimeZoneDTO(
            name: $this->get('name'),
        );
    }
}
