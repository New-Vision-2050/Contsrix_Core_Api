<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteContactMessage\Commands\UpdateWebsiteContactMessageCommand;
use Modules\WebsiteCMS\WebsiteContactMessage\Handlers\UpdateWebsiteContactMessageHandler;

class UpdateWebsiteContactMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'status' => 'nullable|integer|in:0,1',
            'message' => 'nullable|string',
        ];
    }

    public function createUpdateWebsiteContactMessageCommand(): UpdateWebsiteContactMessageCommand
    {
        return new UpdateWebsiteContactMessageCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            phone: $this->get('phone'),
            email: $this->get('email'),
            address: $this->get('address'),
            status: $this->get('status'),
            message: $this->get('message'),
        );
    }
}
