<?php

declare(strict_types=1);

namespace Modules\Shared\Language\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\Language\DTO\CreateLanguageDTO;

class CreateLanguageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateLanguageDTO(): CreateLanguageDTO
    {
        return new CreateLanguageDTO(
            name: $this->get('name'),
        );
    }
}
