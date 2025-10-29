<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\Banner\Commands\UpdateBannerCommand;
use Modules\Ecommerce\Banner\Handlers\UpdateBannerHandler;

class UpdateBannerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'url' => 'sometimes|string|url',
            'type' => 'sometimes|string|in:home,discount,new_arrival,contact_us,about_us',
            'is_active' => 'sometimes|boolean',
            'banner_image' => 'sometimes|file|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ];
    }

    public function messages(): array
    {
        return [
            'url.url' => 'يجب أن يكون الرابط صحيح',
            'type.in' => 'نوع البانر يجب أن يكون أحد القيم المحددة',
            'is_active.boolean' => 'حالة البانر يجب أن تكون صحيح أو خطأ',
            'banner_image.image' => 'يجب أن تكون صورة صحيحة',
            'banner_image.mimes' => 'صورة البانر يجب أن تكون من نوع: jpeg, png, jpg, gif, webp',
            'banner_image.max' => 'حجم صورة البانر يجب ألا يتجاوز 5 ميجابايت',
        ];
    }

    public function createUpdateBannerCommand(): UpdateBannerCommand
    {
        $isActive = $this->has('is_active') ? (bool) $this->input('is_active') : null;
        
        return new UpdateBannerCommand(
            id: Uuid::fromString($this->route('id')),
            url: $this->input('url'),
            type: $this->input('type'),
            isActive: $isActive,
        );
    }
}
