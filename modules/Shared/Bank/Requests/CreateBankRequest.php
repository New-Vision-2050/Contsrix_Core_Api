<?php

declare(strict_types=1);

namespace Modules\Shared\Bank\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\Bank\DTO\CreateBankDTO;

class CreateBankRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateBankDTO(): CreateBankDTO
    {
        return new CreateBankDTO(
            name: $this->get('name'),
        );
    }
}
