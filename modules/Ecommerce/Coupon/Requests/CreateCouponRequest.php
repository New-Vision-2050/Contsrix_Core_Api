<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Requests;

use BasePackage\Shared\Requests\BaseRequest;
use Modules\Ecommerce\Coupon\DTO\CreateCouponDTO;
use Modules\Ecommerce\Coupon\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;

class CreateCouponRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'coupon_type' => 'required|string|in:discount_on_purchase,free_delivery,first_order',
            'title' => 'required|string|max:100',
            'code' => 'required|string|max:15|unique:coupons,code',
            'customer_id' => 'nullable|uuid|exists:users,id',
            'max_usage_per_user' => 'nullable|integer|min:1',
            'discount_type' => 'required|string|in:percentage,fixed',
            'discount_amount' => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'start_date' => 'required|date|after_or_equal:today',
            'expire_date' => 'required|date|after:start_date',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'coupon_type.required' => 'نوع القسيمة مطلوب',
            'coupon_type.in' => 'نوع القسيمة يجب أن يكون أحد القيم المحددة',
            'title.required' => 'عنوان القسيمة مطلوب',
            'title.max' => 'عنوان القسيمة يجب ألا يتجاوز 100 حرف',
            'code.required' => 'رمز القسيمة مطلوب',
            'code.max' => 'رمز القسيمة يجب ألا يتجاوز 15 حرف',
            'code.unique' => 'رمز القسيمة موجود مسبقاً',
            'customer_id.uuid' => 'معرف العميل يجب أن يكون UUID صحيح',
            'customer_id.exists' => 'العميل المحدد غير موجود',
            'max_usage_per_user.integer' => 'الحد الأقصى للاستخدام يجب أن يكون رقم صحيح',
            'max_usage_per_user.min' => 'الحد الأقصى للاستخدام يجب أن يكون أكبر من صفر',
            'discount_type.required' => 'نوع الخصم مطلوب',
            'discount_type.in' => 'نوع الخصم يجب أن يكون نسبة أو مبلغ ثابت',
            'discount_amount.required' => 'مبلغ الخصم مطلوب',
            'discount_amount.numeric' => 'مبلغ الخصم يجب أن يكون رقم',
            'discount_amount.min' => 'مبلغ الخصم يجب أن يكون أكبر من أو يساوي صفر',
            'min_purchase.numeric' => 'الحد الأدنى للشراء يجب أن يكون رقم',
            'min_purchase.min' => 'الحد الأدنى للشراء يجب أن يكون أكبر من أو يساوي صفر',
            'max_discount.numeric' => 'الحد الأقصى للخصم يجب أن يكون رقم',
            'max_discount.min' => 'الحد الأقصى للخصم يجب أن يكون أكبر من أو يساوي صفر',
            'start_date.required' => 'تاريخ البدء مطلوب',
            'start_date.date' => 'تاريخ البدء يجب أن يكون تاريخ صحيح',
            'start_date.after_or_equal' => 'تاريخ البدء يجب أن يكون اليوم أو بعده',
            'expire_date.required' => 'تاريخ الانتهاء مطلوب',
            'expire_date.date' => 'تاريخ الانتهاء يجب أن يكون تاريخ صحيح',
            'expire_date.after' => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء',
            'is_active.boolean' => 'حالة القسيمة يجب أن تكون صحيح أو خطأ',
        ];
    }

    public function createCouponDTO(): CreateCouponDTO
    {
        $tenantId = tenant('id') ?? 'default-company-id';
        
        return new CreateCouponDTO(
            companyId: $tenantId,
            couponType: $this->input('coupon_type'),
            title: $this->input('title'),
            code: $this->input('code'),
            customerId: $this->input('customer_id'),
            maxUsagePerUser: $this->input('max_usage_per_user'),
            discountType: $this->input('discount_type'),
            discountAmount: (float) $this->input('discount_amount'),
            minPurchase: (float) $this->input('min_purchase', 0),
            maxDiscount: (float) $this->input('max_discount', 0),
            startDate: $this->input('start_date'),
            expireDate: $this->input('expire_date'),
            isActive: $this->input('is_active', true)
        );
    }
}
