<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\FlashDeal\Commands\UpdateFlashDealCommand;
use Ramsey\Uuid\Uuid;

class UpdateFlashDealRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'array'],
            'name.ar' => ['sometimes', 'string', 'max:255'],
            'name.en' => ['sometimes', 'string', 'max:255'],
            
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after:start_date'],
            'is_active' => ['sometimes', 'boolean'],
            
            // Image validation
            'flash_deal_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'], // 5MB max
        ];
    }

    public function messages(): array
    {
        return [
            'name.array' => 'اسم العرض يجب أن يكون مصفوفة',
            'name.ar.string' => 'اسم العرض بالعربية يجب أن يكون نص',
            'name.ar.max' => 'اسم العرض بالعربية يجب ألا يتجاوز 255 حرف',
            'name.en.string' => 'اسم العرض بالإنجليزية يجب أن يكون نص',
            'name.en.max' => 'اسم العرض بالإنجليزية يجب ألا يتجاوز 255 حرف',
            
            'start_date.date' => 'تاريخ بداية العرض يجب أن يكون تاريخ صحيح',
            'end_date.date' => 'تاريخ انتهاء العرض يجب أن يكون تاريخ صحيح',
            'end_date.after' => 'تاريخ انتهاء العرض يجب أن يكون بعد تاريخ البداية',
            
            'is_active.boolean' => 'حالة العرض يجب أن تكون صحيح أو خطأ',
            
            'flash_deal_image.file' => 'صورة العرض يجب أن تكون ملف',
            'flash_deal_image.image' => 'صورة العرض يجب أن تكون صورة',
            'flash_deal_image.mimes' => 'صورة العرض يجب أن تكون من نوع: jpeg, png, jpg, gif, webp',
            'flash_deal_image.max' => 'حجم صورة العرض يجب ألا يتجاوز 5 ميجابايت',
        ];
    }

    public function createUpdateFlashDealCommand(): UpdateFlashDealCommand
    {
        return new UpdateFlashDealCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->input('name'),
            startDate: $this->input('start_date'),
            endDate: $this->input('end_date'),
        );
    }
}
