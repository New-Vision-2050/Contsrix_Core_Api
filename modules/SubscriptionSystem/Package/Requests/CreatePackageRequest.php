<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Package\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\SubscriptionSystem\Package\DTO\CreatePackageDTO;

class CreatePackageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreatePackageDTO(): CreatePackageDTO
    {
        return new CreatePackageDTO(
            name: $this->get('name'),
        );
    }
}
