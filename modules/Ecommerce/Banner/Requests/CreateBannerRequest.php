<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\Banner\DTO\CreateBannerDTO;

class CreateBannerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'setting_page_id' => 'required|uuid|exists:setting_pages,id',
            'url' => 'required|string|url',
            'is_active' => 'sometimes|boolean',
            'banner_image' => 'required|file|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'setting_page_id.required' => 'معرف صفحة الإعدادات مطلوب',
            'setting_page_id.uuid' => 'معرف صفحة الإعدادات يجب أن يكون UUID صحيح',
            'setting_page_id.exists' => 'صفحة الإعدادات المحددة غير موجودة',
            'url.required' => 'رابط البانر مطلوب',
            'url.url' => 'يجب أن يكون الرابط صحيح',
            'is_active.boolean' => 'حالة البانر يجب أن تكون صحيح أو خطأ',
            'banner_image.required' => 'صورة البانر مطلوبة',
            'banner_image.image' => 'يجب أن تكون صورة صحيحة',
            'banner_image.mimes' => 'صورة البانر يجب أن تكون من نوع: jpeg, png, jpg, gif, webp',
            'banner_image.max' => 'حجم صورة البانر يجب ألا يتجاوز 5 ميجابايت',
        ];
    }

    public function createCreateBannerDTO(): CreateBannerDTO
    {
        return new CreateBannerDTO(
            companyId: Uuid::fromString(tenant("id")),
            settingPageId: Uuid::fromString($this->input('setting_page_id')),
            url: $this->input('url'),
            isActive: (int) $this->input('is_active', 1),
        );
    }
}
