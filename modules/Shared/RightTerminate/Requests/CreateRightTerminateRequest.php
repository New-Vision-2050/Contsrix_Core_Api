<?php

declare(strict_types=1);

namespace Modules\Shared\RightTerminate\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\RightTerminate\DTO\CreateRightTerminateDTO;

class CreateRightTerminateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateRightTerminateDTO(): CreateRightTerminateDTO
    {
        return new CreateRightTerminateDTO(
            name: $this->get('name'),
        );
    }
}
