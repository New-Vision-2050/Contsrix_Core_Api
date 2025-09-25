<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoAppSetting\DTO\CreateEcoAppSettingDTO;

class CreateEcoAppSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateEcoAppSettingDTO(): CreateEcoAppSettingDTO
    {
        return new CreateEcoAppSettingDTO(
            name: $this->get('name'),
        );
    }
}
