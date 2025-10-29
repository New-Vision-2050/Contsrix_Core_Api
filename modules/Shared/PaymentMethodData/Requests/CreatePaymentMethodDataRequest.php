<?php

declare(strict_types=1);

namespace Modules\Shared\PaymentMethodData\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Shared\PaymentMethodData\DTO\CreatePaymentMethodDataDTO;

class CreatePaymentMethodDataRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'payment_methods' => 'required|array|min:1',
            'payment_methods.*.type' => 'required|string|max:50|unique:payment_method_data,type',
            'payment_methods.*.name' => 'required|array',
            'payment_methods.*.name.ar' => 'required|string|max:100',
            'payment_methods.*.name.en' => 'required|string|max:100',
            'payment_methods.*.is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_methods.required' => 'طرق الدفع مطلوبة',
            'payment_methods.array' => 'طرق الدفع يجب أن تكون مصفوفة',
            'payment_methods.min' => 'يجب إضافة طريقة دفع واحدة على الأقل',
            'payment_methods.*.type.required' => 'نوع طريقة الدفع مطلوب',
            'payment_methods.*.type.string' => 'نوع طريقة الدفع يجب أن يكون نص',
            'payment_methods.*.type.max' => 'نوع طريقة الدفع يجب ألا يتجاوز 50 حرف',
            'payment_methods.*.type.unique' => 'نوع طريقة الدفع موجود مسبقاً',
            'payment_methods.*.name.required' => 'الاسم مطلوب',
            'payment_methods.*.name.array' => 'الاسم يجب أن يكون مصفوفة',
            'payment_methods.*.name.ar.required' => 'الاسم العربي مطلوب',
            'payment_methods.*.name.ar.string' => 'الاسم العربي يجب أن يكون نص',
            'payment_methods.*.name.ar.max' => 'الاسم العربي يجب ألا يتجاوز 100 حرف',
            'payment_methods.*.name.en.required' => 'الاسم الإنجليزي مطلوب',
            'payment_methods.*.name.en.string' => 'الاسم الإنجليزي يجب أن يكون نص',
            'payment_methods.*.name.en.max' => 'الاسم الإنجليزي يجب ألا يتجاوز 100 حرف',
            'payment_methods.*.is_active.boolean' => 'حالة التفعيل يجب أن تكون صحيح أو خطأ',
        ];
    }

    public function createDTOs(): array
    {
        $dtos = [];
        foreach ($this->input('payment_methods', []) as $method) {
            $dtos[] = new CreatePaymentMethodDataDTO(
                type: $method['type'],
                name: $method['name'], // Pass the entire name array
                isActive: $method['is_active'] ?? true,
            );
        }
        return $dtos;
    }
}
