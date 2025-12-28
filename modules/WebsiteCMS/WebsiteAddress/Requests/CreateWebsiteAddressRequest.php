<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAddress\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteAddress\DTO\CreateWebsiteAddressDTO;

class CreateWebsiteAddressRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'nullable|integer|in:0,1',
        ];
    }

    public function createCreateWebsiteAddressDTO(): CreateWebsiteAddressDTO
    {
        return new CreateWebsiteAddressDTO(
            title: [
                'ar' => $this->get('title_ar'),
                'en' => $this->get('title_en'),
            ],
            address: $this->get('address'),
            latitude:(float) $this->get('latitude'),
            longitude: (float) $this->get('longitude'),
            status: (int)$this->get('status', 1),
        );
    }
}
