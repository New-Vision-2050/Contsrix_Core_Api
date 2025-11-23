<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTermAndCondition\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Commands\UpdateWebsiteTermAndConditionForCurrentCompanyCommand;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Commands\UpdateWebsiteTermAndConditionCommand;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Handlers\UpdateWebsiteTermAndConditionHandler;

class UpdateWebsiteTermAndConditionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'content' => 'required|string',
        ];
    }

    public function createUpdateWebsiteTermAndConditionCommand(): UpdateWebsiteTermAndConditionCommand
    {
        return new UpdateWebsiteTermAndConditionCommand(
            id: Uuid::fromString($this->route('id')),
            content: $this->get('content'),
        );
    }
}
