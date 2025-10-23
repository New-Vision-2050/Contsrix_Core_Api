<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;
use Modules\Ecommerce\FeatureDeal\Commands\UpdateFeatureDealCommand;
use Modules\Ecommerce\FeatureDeal\Handlers\UpdateFeatureDealHandler;

class UpdateFeatureDealRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|array',
            'name.ar' => 'sometimes|string|max:255',
            'name.en' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date|after_or_equal:today',
            'end_date' => 'sometimes|date|after:start_date',
            'discount_type' => 'sometimes|string|in:percentage,amount',
            'discount_value' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.array' => 'اسم العرض المميز يجب أن يكون مصفوفة تحتوي على اللغات',
            'name.ar.string' => 'اسم العرض المميز باللغة العربية يجب أن يكون نص',
            'name.ar.max' => 'اسم العرض المميز باللغة العربية يجب أن لا يتجاوز 255 حرف',
            'name.en.string' => 'اسم العرض المميز باللغة الإنجليزية يجب أن يكون نص',
            'name.en.max' => 'اسم العرض المميز باللغة الإنجليزية يجب أن لا يتجاوز 255 حرف',
            'start_date.date' => 'تاريخ البداية يجب أن يكون تاريخ صحيح',
            'start_date.after_or_equal' => 'تاريخ البداية يجب أن يكون اليوم أو بعده',
            'end_date.date' => 'تاريخ النهاية يجب أن يكون تاريخ صحيح',
            'end_date.after' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية',
            'discount_type.in' => 'نوع الخصم يجب أن يكون نسبة أو مبلغ',
            'discount_value.numeric' => 'قيمة الخصم يجب أن تكون رقم',
            'discount_value.min' => 'قيمة الخصم يجب أن تكون أكبر من أو تساوي صفر',
            'is_active.boolean' => 'حالة التفعيل يجب أن تكون صحيح أو خطأ',
        ];
    }

    public function createUpdateFeatureDealCommand(): UpdateFeatureDealCommand
    {
        return new UpdateFeatureDealCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->input('name'),
            startDate: Carbon::parse($this->input('start_date')),
            endDate: Carbon::parse($this->input('end_date')),
            discountType: $this->input('discount_type'),
            discountValue: (float) $this->input('discount_value'),
            isActive: (bool) $this->input('is_active'),
        );
    }
}
