<?php

declare(strict_types=1);

namespace Modules\Shared/Process\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared/Process\DTO\CreateShared/ProcessDTO;

class CreateShared/ProcessRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateShared/ProcessDTO(): CreateShared/ProcessDTO
    {
        return new CreateShared/ProcessDTO(
            name: $this->get('name'),
        );
    }
}
