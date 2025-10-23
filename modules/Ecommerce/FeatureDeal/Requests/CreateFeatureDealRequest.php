<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;
use Modules\Ecommerce\FeatureDeal\DTO\CreateFeatureDealDTO;

class CreateFeatureDealRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|array',
            'name.ar' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'discount_type' => 'required|string|in:percentage,amount',
            'discount_value' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم العرض المميز مطلوب',
            'name.array' => 'اسم العرض المميز يجب أن يكون مصفوفة تحتوي على اللغات',
            'name.ar.required' => 'اسم العرض المميز باللغة العربية مطلوب',
            'name.ar.string' => 'اسم العرض المميز باللغة العربية يجب أن يكون نص',
            'name.ar.max' => 'اسم العرض المميز باللغة العربية يجب أن لا يتجاوز 255 حرف',
            'name.en.required' => 'اسم العرض المميز باللغة الإنجليزية مطلوب',
            'name.en.string' => 'اسم العرض المميز باللغة الإنجليزية يجب أن يكون نص',
            'name.en.max' => 'اسم العرض المميز باللغة الإنجليزية يجب أن لا يتجاوز 255 حرف',
            'start_date.required' => 'تاريخ البداية مطلوب',
            'start_date.date' => 'تاريخ البداية يجب أن يكون تاريخ صحيح',
            'start_date.after_or_equal' => 'تاريخ البداية يجب أن يكون اليوم أو بعده',
            'end_date.required' => 'تاريخ النهاية مطلوب',
            'end_date.date' => 'تاريخ النهاية يجب أن يكون تاريخ صحيح',
            'end_date.after' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية',
            'discount_type.required' => 'نوع الخصم مطلوب',
            'discount_type.in' => 'نوع الخصم يجب أن يكون نسبة أو مبلغ',
            'discount_value.required' => 'قيمة الخصم مطلوبة',
            'discount_value.numeric' => 'قيمة الخصم يجب أن تكون رقم',
            'discount_value.min' => 'قيمة الخصم يجب أن تكون أكبر من أو تساوي صفر',
            'is_active.boolean' => 'حالة التفعيل يجب أن تكون صحيح أو خطأ',
        ];
    }

    public function createCreateFeatureDealDTO(): CreateFeatureDealDTO
    {
        return new CreateFeatureDealDTO(
            companyId: Uuid::fromString(tenant('id')),
            name: $this->input('name'),
            startDate: Carbon::parse($this->input('start_date')),
            endDate: Carbon::parse($this->input('end_date')),
            discountType: $this->input('discount_type'),
            discountValue: (float) $this->input('discount_value'),
            isActive: (bool) $this->input('is_active', true),
        );
    }
}
