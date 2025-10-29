<?php

declare(strict_types=1);

namespace Modules\Ecommerce\SocialMedia\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\SocialMedia\DTO\CreateSocialMediaDTO;

class CreateSocialMediaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'social_icons_id' => 'required|uuid|exists:social_icons,id',
            'url' => 'required|string|url|max:500',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'معرف الشركة مطلوب',
            'company_id.uuid' => 'معرف الشركة يجب أن يكون UUID صالح',
            'company_id.exists' => 'الشركة المحددة غير موجودة',
            'social_icons_id.required' => 'معرف أيقونة وسائل التواصل مطلوب',
            'social_icons_id.uuid' => 'معرف أيقونة وسائل التواصل يجب أن يكون UUID صالح',
            'social_icons_id.exists' => 'أيقونة وسائل التواصل المحددة غير موجودة',
            'url.required' => 'رابط وسائل التواصل مطلوب',
            'url.url' => 'رابط وسائل التواصل يجب أن يكون رابط صالح',
            'url.max' => 'رابط وسائل التواصل يجب ألا يتجاوز 500 حرف',
            'is_active.boolean' => 'يجب أن يكون حقل الحالة صحيح أو خطأ',
        ];
    }

    public function createCreateSocialMediaDTO(): CreateSocialMediaDTO
    {
        return new CreateSocialMediaDTO(
            companyId: Uuid::fromString(tenant("id")),
            socialIconsId: $this->input('social_icons_id'),
            url: $this->input('url'),
            isActive: $this->input('is_active', true),
        );
    }
}
