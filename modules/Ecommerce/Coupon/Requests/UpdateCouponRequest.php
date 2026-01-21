<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\Coupon\Commands\UpdateCouponCommand;

class UpdateCouponRequest extends FormRequest
{
    public function rules(): array
    {
        $couponId = $this->route('id');
        
        return [
            'coupon_type' => 'sometimes|string|in:discount_on_purchase,free_delivery,first_order',
            'title' => 'sometimes|string|max:100',
            'code' => 'sometimes|string|max:15|unique:coupons,code,' . $couponId,
            'customer_id' => 'sometimes|nullable|array',
            'customer_id.*' => 'uuid|exists:users,id',
            'max_usage_per_user' => 'sometimes|nullable|integer|min:1',
            'discount_type' => 'sometimes|string|in:percentage,fixed',
            'discount_amount' => 'sometimes|numeric|min:0',
            'min_purchase' => 'sometimes|nullable|numeric|min:0',
            'max_discount' => 'sometimes|nullable|numeric|min:0',
            'start_date' => 'sometimes|date',
            'expire_date' => 'sometimes|date|after:start_date',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'coupon_type.in' => 'نوع القسيمة يجب أن يكون أحد القيم المحددة',
            'title.max' => 'عنوان القسيمة يجب ألا يتجاوز 100 حرف',
            'code.max' => 'رمز القسيمة يجب ألا يتجاوز 15 حرف',
            'code.unique' => 'رمز القسيمة موجود مسبقاً',
            'customer_id.uuid' => 'معرف العميل يجب أن يكون UUID صحيح',
            'customer_id.exists' => 'العميل المحدد غير موجود',
            'customer_id.array' => 'يجب أن تكون قائمة العملاء مصفوفة',
            'customer_id.*.uuid' => 'معرف العميل يجب أن يكون UUID صحيح',
            'customer_id.*.exists' => 'العميل المحدد غير موجود',
            'max_usage_per_user.integer' => 'الحد الأقصى للاستخدام يجب أن يكون رقم صحيح',
            'max_usage_per_user.min' => 'الحد الأقصى للاستخدام يجب أن يكون أكبر من صفر',
            'discount_type.in' => 'نوع الخصم يجب أن يكون نسبة أو مبلغ ثابت',
            'discount_amount.numeric' => 'مبلغ الخصم يجب أن يكون رقم',
            'discount_amount.min' => 'مبلغ الخصم يجب أن يكون أكبر من أو يساوي صفر',
            'min_purchase.numeric' => 'الحد الأدنى للشراء يجب أن يكون رقم',
            'min_purchase.min' => 'الحد الأدنى للشراء يجب أن يكون أكبر من أو يساوي صفر',
            'max_discount.numeric' => 'الحد الأقصى للخصم يجب أن يكون رقم',
            'max_discount.min' => 'الحد الأقصى للخصم يجب أن يكون أكبر من أو يساوي صفر',
            'start_date.date' => 'تاريخ البدء يجب أن يكون تاريخ صحيح',
            'expire_date.date' => 'تاريخ الانتهاء يجب أن يكون تاريخ صحيح',
            'expire_date.after' => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء',
            'is_active.boolean' => 'حالة القسيمة يجب أن تكون صحيح أو خطأ',
        ];
    }

    public function createUpdateCouponCommand(): UpdateCouponCommand
    {
        $customerInput = $this->input('customer_id');
        $customerId = is_array($customerInput) ? ($customerInput[0] ?? null) : $customerInput;

        return new UpdateCouponCommand(
            id: Uuid::fromString($this->route('id')),
            couponType: $this->input('coupon_type'),
            title: $this->input('title'),
            code: $this->input('code'),
            customerId: $customerId,
            maxUsagePerUser: $this->input('max_usage_per_user'),
            discountType: $this->input('discount_type'),
            discountAmount: $this->input('discount_amount') ? (float) $this->input('discount_amount') : null,
            minPurchase: $this->input('min_purchase') ? (float) $this->input('min_purchase') : null,
            maxDiscount: $this->input('max_discount') ? (float) $this->input('max_discount') : null,
            startDate: $this->input('start_date'),
            expireDate: $this->input('expire_date'),
            isActive: $this->input('is_active')
        );
    }
}
