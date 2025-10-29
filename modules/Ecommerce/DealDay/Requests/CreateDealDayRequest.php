<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\DealDay\DTO\CreateDealDayDTO;

class CreateDealDayRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|array',
            'name.ar' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'product_id' => 'required|uuid|exists:eco_products,id',
            'discount_type' => 'required|string|in:percentage,amount',
            'discount_value' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم العرض مطلوب',
            'name.array' => 'اسم العرض يجب أن يكون مصفوفة تحتوي على اللغات',
            'name.ar.required' => 'اسم العرض باللغة العربية مطلوب',
            'name.ar.string' => 'اسم العرض باللغة العربية يجب أن يكون نص',
            'name.ar.max' => 'اسم العرض باللغة العربية يجب أن لا يتجاوز 255 حرف',
            'name.en.required' => 'اسم العرض باللغة الإنجليزية مطلوب',
            'name.en.string' => 'اسم العرض باللغة الإنجليزية يجب أن يكون نص',
            'name.en.max' => 'اسم العرض باللغة الإنجليزية يجب أن لا يتجاوز 255 حرف',
            'product_id.required' => 'المنتج مطلوب',
            'product_id.uuid' => 'معرف المنتج غير صحيح',
            'product_id.exists' => 'المنتج المحدد غير موجود',
            'discount_type.required' => 'نوع الخصم مطلوب',
            'discount_type.in' => 'نوع الخصم يجب أن يكون نسبة أو مبلغ',
            'discount_value.required' => 'قيمة الخصم مطلوبة',
            'discount_value.numeric' => 'قيمة الخصم يجب أن تكون رقم',
            'discount_value.min' => 'قيمة الخصم يجب أن تكون أكبر من أو تساوي صفر',
            'is_active.boolean' => 'حالة التفعيل يجب أن تكون صحيح أو خطأ',
        ];
    }

    public function createCreateDealDayDTO(): CreateDealDayDTO
    {
        return new CreateDealDayDTO(
            companyId: Uuid::fromString(tenant('id')),
            name: $this->input('name'),
            productId: Uuid::fromString($this->input('product_id')),
            discountType: $this->input('discount_type'),
            discountValue: (float) $this->input('discount_value'),
            isActive: (bool) $this->input('is_active', true),
        );
    }
}
