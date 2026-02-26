<?php

declare(strict_types=1);

namespace Modules\TermServiceSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\TermServiceSetting\Commands\UpdateTermServiceSettingCommand;
use Modules\TermServiceSetting\Handlers\UpdateTermServiceSettingHandler;

class UpdateTermServiceSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateTermServiceSettingCommand(): UpdateTermServiceSettingCommand
    {
        return new UpdateTermServiceSettingCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
