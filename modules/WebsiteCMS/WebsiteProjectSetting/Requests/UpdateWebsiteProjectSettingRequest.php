<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProjectSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteProjectSetting\Commands\UpdateWebsiteProjectSettingCommand;
use Modules\WebsiteCMS\WebsiteProjectSetting\Handlers\UpdateWebsiteProjectSettingHandler;

class UpdateWebsiteProjectSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
        ];
    }

    public function createUpdateWebsiteProjectSettingCommand(): UpdateWebsiteProjectSettingCommand
    {
        return new UpdateWebsiteProjectSettingCommand(
            id: Uuid::fromString($this->route('id')),
            name: [
                'ar' => $this->get('name_ar'),
                'en' => $this->get('name_en'),
            ],
        );
    }
}
