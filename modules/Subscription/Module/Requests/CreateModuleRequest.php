<?php

declare(strict_types=1);

namespace Modules\Subscription\Module\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Subscription\Module\DTO\CreateModuleDTO;

class CreateModuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateModuleDTO(): CreateModuleDTO
    {
        return new CreateModuleDTO(
            name: $this->get('name'),
        );
    }
}
