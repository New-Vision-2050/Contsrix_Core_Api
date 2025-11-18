<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteSetting\Commands\UpdateWebsiteSettingCommand;

class UpdateWebsiteSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'main_color' => 'nullable|string|max:50',
            'second_color' => 'nullable|string|max:50',
            'background_color' => 'nullable|string|max:50',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'website_address' => 'nullable|string|max:255|url',
        ];
    }

    public function messages(): array
    {
        return [
            'main_color.string' => 'Main color must be a valid color string',
            'second_color.string' => 'Second color must be a valid color string',
            'background_color.string' => 'Background color must be a valid color string',
            'logo.image' => 'Logo must be an image file',
            'logo.mimes' => 'Logo must be a file of type: jpeg, png, jpg, gif, svg',
            'logo.max' => 'Logo may not be greater than 2MB',
            'website_address.url' => 'Website address must be a valid URL',
        ];
    }

    public function createUpdateWebsiteSettingCommand(): UpdateWebsiteSettingCommand
    {
        return new UpdateWebsiteSettingCommand(
            id: Uuid::fromString($this->route('id')),
            main_color: $this->get('main_color'),
            second_color: $this->get('second_color'),
            background_color: $this->get('background_color'),
            logo: $this->file('logo'),
            website_address: $this->get('website_address'),
        );
    }
}
