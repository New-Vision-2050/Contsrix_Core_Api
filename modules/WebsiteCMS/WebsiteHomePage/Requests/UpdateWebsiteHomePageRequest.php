<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteHomePage\Commands\UpdateWebsiteHomePageCommand;
use Modules\WebsiteCMS\WebsiteHomePage\Handlers\UpdateWebsiteHomePageHandler;

class UpdateWebsiteHomePageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateWebsiteHomePageCommand(): UpdateWebsiteHomePageCommand
    {
        return new UpdateWebsiteHomePageCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
