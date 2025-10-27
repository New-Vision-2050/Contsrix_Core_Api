<?php

declare(strict_types=1);

namespace Modules\Ecommerce\SocialMedia\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\SocialMedia\Commands\UpdateSocialMediaCommand;

class UpdateSocialMediaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'social_icons_id' => 'sometimes|uuid|exists:social_icons,id',
            'url' => 'sometimes|string|url|max:500',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'social_icons_id.uuid' => 'معرف أيقونة وسائل التواصل يجب أن يكون UUID صالح',
            'social_icons_id.exists' => 'أيقونة وسائل التواصل المحددة غير موجودة',
            'url.url' => 'رابط وسائل التواصل يجب أن يكون رابط صالح',
            'url.max' => 'رابط وسائل التواصل يجب ألا يتجاوز 500 حرف',
            'is_active.boolean' => 'يجب أن يكون حقل الحالة صحيح أو خطأ',
        ];
    }

    public function createUpdateSocialMediaCommand(): UpdateSocialMediaCommand
    {
        return new UpdateSocialMediaCommand(
            id: Uuid::fromString($this->route('id')),
            socialIconsId: $this->input('social_icons_id'),
            url: $this->input('url'),
            isActive: $this->input('is_active'),
        );
    }
}
