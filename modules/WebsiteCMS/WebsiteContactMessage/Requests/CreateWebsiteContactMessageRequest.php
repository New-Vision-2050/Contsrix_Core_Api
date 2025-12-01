<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteContactMessage\DTO\CreateWebsiteContactMessageDTO;

class CreateWebsiteContactMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'address' => 'nullable|string|max:500',
            'message' => 'required|string',
        ];
    }

    public function createCreateWebsiteContactMessageDTO(): CreateWebsiteContactMessageDTO
    {
        return new CreateWebsiteContactMessageDTO(
            name: $this->get('name'),
            phone: $this->get('phone'),
            email: $this->get('email'),
            address: $this->get('address'),
            status: 0,
            message: $this->get('message'),
        );
    }
}
