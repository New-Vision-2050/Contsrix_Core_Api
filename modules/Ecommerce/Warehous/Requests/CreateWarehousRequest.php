<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\Warehous\DTO\CreateWarehousDTO;

class CreateWarehousRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateWarehousDTO(): CreateWarehousDTO
    {
        return new CreateWarehousDTO(
            companyId: Uuid::fromString(tenant("id")),
            name: $this->get('name'),
        );
    }
}
