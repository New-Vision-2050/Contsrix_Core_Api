<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTermAndCondition\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Commands\UpdateWebsiteTermAndConditionCommand;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Handlers\UpdateWebsiteTermAndConditionHandler;

class UpdateWebsiteTermAndConditionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateWebsiteTermAndConditionCommand(): UpdateWebsiteTermAndConditionCommand
    {
        return new UpdateWebsiteTermAndConditionCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
