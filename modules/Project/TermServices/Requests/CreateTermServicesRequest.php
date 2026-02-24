<?php

declare(strict_types=1);

namespace Modules\Project\TermServices\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Project\TermServices\DTO\CreateTermServicesDTO;

class CreateTermServicesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateTermServicesDTO(): CreateTermServicesDTO
    {
        return new CreateTermServicesDTO(
            name: $this->get('name'),
        );
    }
}
