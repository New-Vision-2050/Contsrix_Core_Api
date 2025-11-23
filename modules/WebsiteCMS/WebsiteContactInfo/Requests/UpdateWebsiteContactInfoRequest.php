<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactInfo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\WebsiteCMS\WebsiteContactInfo\DTO\UpdateWebsiteContactInfoDTO;

class UpdateWebsiteContactInfoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
        ];
    }

    public function toDTO(): UpdateWebsiteContactInfoDTO
    {
        return new UpdateWebsiteContactInfoDTO(
            email: $this->get('email'),
            phone: $this->get('phone'),
        );
    }
}
