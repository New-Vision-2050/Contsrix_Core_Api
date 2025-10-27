<?php

declare(strict_types=1);

namespace Modules\Shared\SocialIcon\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\SocialIcon\Commands\UpdateSocialIconCommand;
use Modules\Shared\SocialIcon\Handlers\UpdateSocialIconHandler;

class UpdateSocialIconRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'web_icon' => 'sometimes|string|max:500',
            'mobile_icon' => 'sometimes|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'اسم الأيقونة الاجتماعية يجب أن يكون نص',
            'name.max' => 'اسم الأيقونة الاجتماعية يجب ألا يتجاوز 255 حرف',
            'web_icon.string' => 'أيقونة الويب يجب أن تكون نص',
            'web_icon.max' => 'أيقونة الويب يجب ألا تتجاوز 500 حرف',
            'mobile_icon.string' => 'أيقونة الموبايل يجب أن تكون نص',
            'mobile_icon.max' => 'أيقونة الموبايل يجب ألا تتجاوز 500 حرف',
        ];
    }

    public function createUpdateSocialIconCommand(): UpdateSocialIconCommand
    {
        return new UpdateSocialIconCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->input('name'),
            webIcon: $this->input('web_icon'),
            mobileIcon: $this->input('mobile_icon'),
        );
    }
}
