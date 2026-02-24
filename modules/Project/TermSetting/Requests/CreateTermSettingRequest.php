<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Project\TermSetting\DTO\CreateTermSettingDTO;

class CreateTermSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateTermSettingDTO(): CreateTermSettingDTO
    {
        return new CreateTermSettingDTO(
            name: $this->get('name'),
        );
    }
}
