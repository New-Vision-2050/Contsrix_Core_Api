<?php

declare(strict_types=1);

namespace Modules\Process\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Process\DTO\CreateProcessDTO;

class CreateProcessRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateProcessDTO(): CreateProcessDTO
    {
        return new CreateProcessDTO(
            name: $this->get('name'),
        );
    }
}
