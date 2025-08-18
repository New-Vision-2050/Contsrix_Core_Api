<?php

declare(strict_types=1);

namespace Modules\Test\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Test\DTO\CreateTestDTO;

class CreateTestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateTestDTO(): CreateTestDTO
    {
        return new CreateTestDTO(
            name: $this->get('name'),
        );
    }
}
