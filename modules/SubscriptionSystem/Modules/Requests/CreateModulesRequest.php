<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Modules\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\SubscriptionSystem\Modules\DTO\CreateModulesDTO;

class CreateModulesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateModulesDTO(): CreateModulesDTO
    {
        return new CreateModulesDTO(
            name: $this->get('name'),
        );
    }
}
