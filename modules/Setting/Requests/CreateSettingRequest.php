<?php

declare(strict_types=1);

namespace Modules\Setting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Setting\DTO\CreateSettingDTO;

class CreateSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'key' => 'required|string',
            'value' => 'required|string',
        ];
    }

    public function createCreateSettingDTO(): CreateSettingDTO
    {
        return new CreateSettingDTO(
            key: $this->get('key'),
            value: $this->get('value'),
        );
    }
}
