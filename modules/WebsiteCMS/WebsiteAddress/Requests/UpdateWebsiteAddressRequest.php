<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAddress\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteAddress\Commands\UpdateWebsiteAddressCommand;
use Modules\WebsiteCMS\WebsiteAddress\Handlers\UpdateWebsiteAddressHandler;

class UpdateWebsiteAddressRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'city_id' => 'required|integer|exists:cities,id',
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'nullable|integer|in:0,1',
        ];
    }

    public function createUpdateWebsiteAddressCommand(): UpdateWebsiteAddressCommand
    {
        return new UpdateWebsiteAddressCommand(
            id: Uuid::fromString($this->route('id')),
            cityId: (int) $this->get('city_id'),
            title: [
                'ar' => $this->get('title_ar'),
                'en' => $this->get('title_en'),
            ],
            latitude:(float) $this->get('latitude'),
            longitude: (float) $this->get('longitude'),
            status: (int)$this->get('status', 1),
        );
    }
}
