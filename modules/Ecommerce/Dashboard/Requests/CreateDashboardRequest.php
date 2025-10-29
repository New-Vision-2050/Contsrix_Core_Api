<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Dashboard\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\Dashboard\DTO\CreateDashboardDTO;

class CreateDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateDashboardDTO(): CreateDashboardDTO
    {
        return new CreateDashboardDTO(
            name: $this->get('name'),
        );
    }
}
