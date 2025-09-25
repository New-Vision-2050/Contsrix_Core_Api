<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoAppSetting\Commands\UpdateEcoAppSettingCommand;
use Modules\Ecommerce\EcoAppSetting\Handlers\UpdateEcoAppSettingHandler;

class UpdateEcoAppSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateEcoAppSettingCommand(): UpdateEcoAppSettingCommand
    {
        return new UpdateEcoAppSettingCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
