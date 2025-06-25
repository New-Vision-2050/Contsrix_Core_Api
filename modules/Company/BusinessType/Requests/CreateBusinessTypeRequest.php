<?php

declare(strict_types=1);

namespace Modules\Company\BusinessType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\BusinessType\DTO\CreateBusinessTypeDTO;

class CreateBusinessTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'description' => 'required|string',   
        ];
    }

    public function createCreateBusinessTypeDTO(): CreateBusinessTypeDTO
    {
        return new CreateBusinessTypeDTO(
            name: $this->get('name'),
            description: $this->get('description')
        );
    }
}
