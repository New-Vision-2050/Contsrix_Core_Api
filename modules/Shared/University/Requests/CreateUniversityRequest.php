<?php

declare(strict_types=1);

namespace Modules\Shared\University\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Shared\University\DTO\CreateUniversityDTO;

class CreateUniversityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'country_id' => 'required|string',
            'url' => 'nullable|string',
        ];
    }

    public function createCreateUniversityDTO(): CreateUniversityDTO
    {
        return new CreateUniversityDTO(
            name: $this->get('name'),
            countryId: $this->get('country_id'),
            url: $this->get('name'),
        );
    }
}
