<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\Order\DTO\CreateOrderDTO;

class CreateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'is_guest' => 'required|boolean',
            'payment_method' => 'required|string|in:cash,credit_card,debit_card,bank_transfer,wallet,cod',
            'shipping_address' => 'required|string|max:500',
            'order_note' => 'nullable|string|max:1000',
            
            // Order items validation
            'order_items' => 'required|array|min:1',
            'order_items.*.product_id' => 'required|uuid|exists:eco_products,id',
            'order_items.*.quantity' => 'required|integer|min:1',
        ];

        // Conditional validation based on is_guest
        if ($this->input('is_guest') === true) {
            // Guest order - customer_id should be empty
            $rules['customer_id'] = 'nullable|string|max:0'; // Empty string
            $rules['customer_name'] = 'required|string|max:100';
            $rules['customer_phone'] = 'required|string|max:20';
            $rules['customer_email'] = 'required|email|max:100';
        } else {
            // Registered user - customer_id required
            $rules['customer_id'] = 'required|uuid|exists:users,id';
            $rules['customer_name'] = 'nullable|string|max:100';
            $rules['customer_phone'] = 'nullable|string|max:20';
            $rules['customer_email'] = 'nullable|email|max:100';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            // Customer validation messages
            'customer_id.required' => 'معرف العميل مطلوب للمستخدمين المسجلين',
            'customer_id.uuid' => 'معرف العميل يجب أن يكون UUID صحيح',
            'customer_id.exists' => 'العميل المحدد غير موجود',
            'customer_id.max' => 'معرف العميل يجب أن يكون فارغ للضيوف',
            
            // Guest customer info messages
            'customer_name.required' => 'اسم العميل مطلوب للضيوف',
            'customer_name.string' => 'اسم العميل يجب أن يكون نص',
            'customer_name.max' => 'اسم العميل لا يجب أن يتجاوز 100 حرف',
            'customer_phone.required' => 'رقم الهاتف مطلوب للضيوف',
            'customer_phone.string' => 'رقم الهاتف يجب أن يكون نص',
            'customer_phone.max' => 'رقم الهاتف لا يجب أن يتجاوز 20 حرف',
            'customer_email.required' => 'البريد الإلكتروني مطلوب للضيوف',
            'customer_email.email' => 'البريد الإلكتروني غير صحيح',
            'customer_email.max' => 'البريد الإلكتروني لا يجب أن يتجاوز 100 حرف',
            
            // General validation messages
            'is_guest.required' => 'حقل نوع العميل مطلوب',
            'is_guest.boolean' => 'حقل الضيف يجب أن يكون صحيح أو خطأ',
            'payment_method.required' => 'طريقة الدفع مطلوبة',
            'payment_method.string' => 'طريقة الدفع يجب أن تكون نص',
            'payment_method.in' => 'طريقة الدفع غير صحيحة. القيم المسموحة: نقدي، بطاقة ائتمان، بطاقة خصم، تحويل بنكي، محفظة، دفع عند الاستلام',
            'shipping_address.required' => 'عنوان الشحن مطلوب',
            'shipping_address.string' => 'عنوان الشحن يجب أن تكون نص',
            'shipping_address.max' => 'عنوان الشحن لا يجب أن يتجاوز 500 حرف',
            'order_note.string' => 'ملاحظة الطلب يجب أن تكون نص',
            'order_note.max' => 'ملاحظة الطلب لا يجب أن تتجاوز 1000 حرف',
            
            // Order items messages
            'order_items.required' => 'عناصر الطلب مطلوبة',
            'order_items.array' => 'عناصر الطلب يجب أن تكون مصفوفة',
            'order_items.min' => 'يجب أن يحتوي الطلب على عنصر واحد على الأقل',
            'order_items.*.product_id.required' => 'معرف المنتج مطلوب',
            'order_items.*.product_id.uuid' => 'معرف المنتج يجب أن يكون UUID صحيح',
            'order_items.*.product_id.exists' => 'المنتج المحدد غير موجود',
            'order_items.*.quantity.required' => 'كمية المنتج مطلوبة',
            'order_items.*.quantity.integer' => 'كمية المنتج يجب أن تكون رقم صحيح',
            'order_items.*.quantity.min' => 'كمية المنتج يجب أن تكون 1 على الأقل',
        ];
    }
    public function createCreateOrderDTO(): CreateOrderDTO
    {
        $isGuest = $this->input('is_guest', false);

        return new CreateOrderDTO(
            companyId: Uuid::fromString(tenant("id")),
            customerId: $this->input('customer_id'),
            isGuest: $isGuest,
            paymentMethod: $this->input('payment_method'),
            shippingAddress: $this->input('shipping_address'),
            orderNote: $this->input('order_note'),
            orderItems: $this->input('order_items', []),
            customerName: $this->input('customer_name'),
            customerPhone: $this->input('customer_phone'),
            customerEmail: $this->input('customer_email'),
        );
    }


}
