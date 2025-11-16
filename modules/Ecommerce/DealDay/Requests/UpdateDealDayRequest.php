<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\DealDay\Commands\UpdateDealDayCommand;
use Modules\Ecommerce\DealDay\Handlers\UpdateDealDayHandler;

class UpdateDealDayRequest extends FormRequest
{
    public function rules(): array
    {
        $companyId = tenant('id');

        return [
            'name' => 'sometimes|array',
            'name.ar' => 'sometimes|string|max:255',
            'name.en' => 'sometimes|string|max:255',
            'product_id' => 'sometimes|uuid|exists:eco_products,id',
            'discount_type' => 'sometimes|string|in:percentage,amount',
            'discount_value' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'date_offer' => [
                'sometimes',
                'date_format:Y-m-d',
                function ($attribute, $value, $fail) use ($companyId) {
                    if ($value === null) {
                        return;
                    }

                    $exists = \Modules\Ecommerce\DealDay\Models\DealDay::query()
                        ->where('company_id', $companyId)
                        ->whereDate('date_offer', $value)
                        ->where('id', '<>', $this->route('id'))
                        ->exists();

                    if ($exists) {
                        $fail('يوجد عرض يومي مسجل لهذا التاريخ بالفعل.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.array' => 'اسم العرض يجب أن يكون مصفوفة تحتوي على اللغات',
            'name.ar.string' => 'اسم العرض باللغة العربية يجب أن يكون نص',
            'name.ar.max' => 'اسم العرض باللغة العربية يجب أن لا يتجاوز 255 حرف',
            'name.en.string' => 'اسم العرض باللغة الإنجليزية يجب أن يكون نص',
            'name.en.max' => 'اسم العرض باللغة الإنجليزية يجب أن لا يتجاوز 255 حرف',
            'product_id.uuid' => 'معرف المنتج غير صحيح',
            'product_id.exists' => 'المنتج المحدد غير موجود',
            'discount_type.in' => 'نوع الخصم يجب أن يكون نسبة أو مبلغ',
            'discount_value.numeric' => 'قيمة الخصم يجب أن تكون رقم',
            'discount_value.min' => 'قيمة الخصم يجب أن تكون أكبر من أو تساوي صفر',
            'is_active.boolean' => 'حالة التفعيل يجب أن تكون صحيح أو خطأ',
            'date_offer.date_format' => 'صيغة التاريخ يجب أن تكون Y-m-d',
        ];
    }

    public function createUpdateDealDayCommand(): UpdateDealDayCommand
    {
        return new UpdateDealDayCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->input('name'),
            productId: $this->input('product_id') ? Uuid::fromString($this->input('product_id')) : null,
            discountType: $this->input('discount_type'),
            discountValue: (float) $this->input('discount_value'),
            dateOffer: $this->input('date_offer'),
        );
    }
}
