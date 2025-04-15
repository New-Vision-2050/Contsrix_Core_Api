<?php

declare(strict_types=1);

namespace Modules\Shared\University\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\University\DTO\CreateUniversityDTO;

class CreateUniversityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateUniversityDTO(): CreateUniversityDTO
    {
        return new CreateUniversityDTO(
            name: $this->get('name'),
        );
    }
}
