<?php

declare(strict_types=1);

namespace Modules\Tenant\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Tenant\DTO\CreateTenantDTO;

class CreateTenantRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateTenantDTO(): CreateTenantDTO
    {
        return new CreateTenantDTO(
            name: $this->get('name'),
        );
    }
}
