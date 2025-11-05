<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class ExportCouponRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['nullable', 'array'],
            'ids.*' => ['uuid', 'exists:coupons,id'],
            'format' => ['nullable', 'string', 'in:xlsx,csv'],
            'title' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'coupon_type' => ['nullable', 'string', 'in:discount_on_purchase,free_delivery,first_order'],
            'coupon_types' => ['nullable', 'array'],
            'coupon_types.*' => ['string', 'in:discount_on_purchase,free_delivery,first_order'],
            'discount_type' => ['nullable', 'string', 'in:percentage,fixed'],
            'discount_types' => ['nullable', 'array'],
            'discount_types.*' => ['string', 'in:percentage,fixed'],
            'is_active' => ['nullable', 'boolean'],
            'company_id' => ['nullable', 'uuid', 'exists:companies,id'],
            'company_ids' => ['nullable', 'array'],
            'company_ids.*' => ['uuid', 'exists:companies,id'],
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['uuid', 'exists:customers,id'],
            'has_customer' => ['nullable', 'boolean'],
            'discount_amount_from' => ['nullable', 'numeric', 'min:0'],
            'discount_amount_to' => ['nullable', 'numeric', 'min:0'],
            'min_purchase_from' => ['nullable', 'numeric', 'min:0'],
            'min_purchase_to' => ['nullable', 'numeric', 'min:0'],
            'max_discount_from' => ['nullable', 'numeric', 'min:0'],
            'max_discount_to' => ['nullable', 'numeric', 'min:0'],
            'max_usage_per_user' => ['nullable', 'integer', 'min:1'],
            'max_usage_per_user_from' => ['nullable', 'integer', 'min:1'],
            'max_usage_per_user_to' => ['nullable', 'integer', 'min:1'],
            'unlimited_usage' => ['nullable', 'boolean'],
            'start_date_from' => ['nullable', 'date'],
            'start_date_to' => ['nullable', 'date'],
            'expire_date_from' => ['nullable', 'date'],
            'expire_date_to' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:valid,expired,upcoming,running,active,inactive'],
            'high_value' => ['nullable', 'numeric', 'min:0'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date'],
            'created_in_last_days' => ['nullable', 'integer', 'min:1'],
            'expiring_in_next_days' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.array' => 'معرفات الكوبونات يجب أن تكون مصفوفة',
            'ids.*.uuid' => 'معرف الكوبون يجب أن يكون UUID صحيح',
            'ids.*.exists' => 'الكوبون المحدد غير موجود',
            'format.string' => 'صيغة التصدير يجب أن تكون نص',
            'format.in' => 'صيغة التصدير يجب أن تكون xlsx أو csv',
            'title.string' => 'عنوان الكوبون يجب أن يكون نص',
            'title.max' => 'عنوان الكوبون يجب ألا يتجاوز 255 حرف',
            'code.string' => 'كود الكوبون يجب أن يكون نص',
            'code.max' => 'كود الكوبون يجب ألا يتجاوز 255 حرف',
            'coupon_type.string' => 'نوع الكوبون يجب أن يكون نص',
            'coupon_type.in' => 'نوع الكوبون يجب أن يكون أحد القيم المسموحة',
            'coupon_types.array' => 'أنواع الكوبونات يجب أن تكون مصفوفة',
            'coupon_types.*.string' => 'نوع الكوبون يجب أن يكون نص',
            'coupon_types.*.in' => 'نوع الكوبون يجب أن يكون أحد القيم المسموحة',
            'discount_type.string' => 'نوع الخصم يجب أن يكون نص',
            'discount_type.in' => 'نوع الخصم يجب أن يكون نسبة مئوية أو مبلغ ثابت',
            'discount_types.array' => 'أنواع الخصم يجب أن تكون مصفوفة',
            'discount_types.*.string' => 'نوع الخصم يجب أن يكون نص',
            'discount_types.*.in' => 'نوع الخصم يجب أن يكون نسبة مئوية أو مبلغ ثابت',
            'is_active.boolean' => 'حالة النشاط يجب أن تكون صحيح أو خطأ',
            'company_id.uuid' => 'معرف الشركة يجب أن يكون UUID صحيح',
            'company_id.exists' => 'الشركة المحددة غير موجودة',
            'company_ids.array' => 'معرفات الشركات يجب أن تكون مصفوفة',
            'company_ids.*.uuid' => 'معرف الشركة يجب أن يكون UUID صحيح',
            'company_ids.*.exists' => 'الشركة المحددة غير موجودة',
            'customer_id.uuid' => 'معرف العميل يجب أن يكون UUID صحيح',
            'customer_id.exists' => 'العميل المحدد غير موجود',
            'customer_ids.array' => 'معرفات العملاء يجب أن تكون مصفوفة',
            'customer_ids.*.uuid' => 'معرف العميل يجب أن يكون UUID صحيح',
            'customer_ids.*.exists' => 'العميل المحدد غير موجود',
            'has_customer.boolean' => 'وجود عميل مخصص يجب أن يكون صحيح أو خطأ',
            'discount_amount_from.numeric' => 'قيمة الخصم من يجب أن تكون رقم',
            'discount_amount_from.min' => 'قيمة الخصم من يجب أن تكون 0 أو أكثر',
            'discount_amount_to.numeric' => 'قيمة الخصم إلى يجب أن تكون رقم',
            'discount_amount_to.min' => 'قيمة الخصم إلى يجب أن تكون 0 أو أكثر',
            'min_purchase_from.numeric' => 'الحد الأدنى للشراء من يجب أن يكون رقم',
            'min_purchase_from.min' => 'الحد الأدنى للشراء من يجب أن يكون 0 أو أكثر',
            'min_purchase_to.numeric' => 'الحد الأدنى للشراء إلى يجب أن يكون رقم',
            'min_purchase_to.min' => 'الحد الأدنى للشراء إلى يجب أن يكون 0 أو أكثر',
            'max_discount_from.numeric' => 'الحد الأقصى للخصم من يجب أن يكون رقم',
            'max_discount_from.min' => 'الحد الأقصى للخصم من يجب أن يكون 0 أو أكثر',
            'max_discount_to.numeric' => 'الحد الأقصى للخصم إلى يجب أن يكون رقم',
            'max_discount_to.min' => 'الحد الأقصى للخصم إلى يجب أن يكون 0 أو أكثر',
            'max_usage_per_user.integer' => 'الاستخدام الأقصى لكل مستخدم يجب أن يكون رقم صحيح',
            'max_usage_per_user.min' => 'الاستخدام الأقصى لكل مستخدم يجب أن يكون 1 أو أكثر',
            'max_usage_per_user_from.integer' => 'الاستخدام الأقصى من يجب أن يكون رقم صحيح',
            'max_usage_per_user_from.min' => 'الاستخدام الأقصى من يجب أن يكون 1 أو أكثر',
            'max_usage_per_user_to.integer' => 'الاستخدام الأقصى إلى يجب أن يكون رقم صحيح',
            'max_usage_per_user_to.min' => 'الاستخدام الأقصى إلى يجب أن يكون 1 أو أكثر',
            'unlimited_usage.boolean' => 'الاستخدام غير المحدود يجب أن يكون صحيح أو خطأ',
            'start_date_from.date' => 'تاريخ البداية من يجب أن يكون تاريخ صحيح',
            'start_date_to.date' => 'تاريخ البداية إلى يجب أن يكون تاريخ صحيح',
            'expire_date_from.date' => 'تاريخ الانتهاء من يجب أن يكون تاريخ صحيح',
            'expire_date_to.date' => 'تاريخ الانتهاء إلى يجب أن يكون تاريخ صحيح',
            'status.string' => 'الحالة يجب أن تكون نص',
            'status.in' => 'الحالة يجب أن تكون أحد القيم المسموحة',
            'high_value.numeric' => 'القيمة العالية يجب أن تكون رقم',
            'high_value.min' => 'القيمة العالية يجب أن تكون 0 أو أكثر',
            'created_from.date' => 'تاريخ الإنشاء من يجب أن يكون تاريخ صحيح',
            'created_to.date' => 'تاريخ الإنشاء إلى يجب أن يكون تاريخ صحيح',
            'created_in_last_days.integer' => 'المُنشأة في آخر أيام يجب أن تكون رقم صحيح',
            'created_in_last_days.min' => 'المُنشأة في آخر أيام يجب أن تكون 1 أو أكثر',
            'expiring_in_next_days.integer' => 'المنتهية في الأيام القادمة يجب أن تكون رقم صحيح',
            'expiring_in_next_days.min' => 'المنتهية في الأيام القادمة يجب أن تكون 1 أو أكثر',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add proper authorization logic here if needed
    }
}
