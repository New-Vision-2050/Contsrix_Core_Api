<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\ActivityLog\DTO\CreateActivityLogDTO;

class CreateActivityLogRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateActivityLogDTO(): CreateActivityLogDTO
    {
        return new CreateActivityLogDTO(
            name: $this->get('name'),
        );
    }
}
