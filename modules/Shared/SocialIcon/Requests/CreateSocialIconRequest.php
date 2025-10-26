<?php

declare(strict_types=1);

namespace Modules\Shared\SocialIcon\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\SocialIcon\DTO\CreateSocialIconDTO;

class CreateSocialIconRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'web_icon' => 'required|string|max:500',
            'mobile_icon' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الأيقونة الاجتماعية مطلوب',
            'name.string' => 'اسم الأيقونة الاجتماعية يجب أن يكون نص',
            'name.max' => 'اسم الأيقونة الاجتماعية يجب ألا يتجاوز 255 حرف',
            'web_icon.required' => 'أيقونة الويب مطلوبة',
            'web_icon.string' => 'أيقونة الويب يجب أن تكون نص',
            'web_icon.max' => 'أيقونة الويب يجب ألا تتجاوز 500 حرف',
            'mobile_icon.required' => 'أيقونة الموبايل مطلوبة',
            'mobile_icon.string' => 'أيقونة الموبايل يجب أن تكون نص',
            'mobile_icon.max' => 'أيقونة الموبايل يجب ألا تتجاوز 500 حرف',
        ];
    }

    public function createCreateSocialIconDTO(): CreateSocialIconDTO
    {
        return new CreateSocialIconDTO(
            name: $this->input('name'),
            webIcon: $this->input('web_icon'),
            mobileIcon: $this->input('mobile_icon'),
        );
    }
}
