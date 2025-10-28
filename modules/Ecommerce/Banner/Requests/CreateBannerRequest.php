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
            'url' => 'required|string|url',
            'type' => 'required|string|in:home,discount,new_arrival,contact_us, about_us',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'banner_image' => 'required|file|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'url.required' => 'رابط البانر مطلوب',
            'url.url' => 'يجب أن يكون الرابط صحيح',
            'type.required' => 'نوع البانر مطلوب',
            'type.in' => 'نوع البانر يجب أن يكون أحد القيم التالية: home, discount, new_arrival, contact_us, about_us',
            'title.string' => 'عنوان البانر يجب أن يكون نص',
            'title.max' => 'عنوان البانر يجب ألا يتجاوز 255 حرف',
            'description.string' => 'وصف البانر يجب أن يكون نص',
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
            url: $this->input('url'),
            type: $this->input('type'),
            title: $this->input('title'),
            description: $this->input('description'),
            isActive: (int) $this->input('is_active', 1),
        );
    }
}
