<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Project\TermSetting\Commands\UpdateTermSettingCommand;
use Modules\Project\TermSetting\Handlers\UpdateTermSettingHandler;

class UpdateTermSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateTermSettingCommand(): UpdateTermSettingCommand
    {
        return new UpdateTermSettingCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
