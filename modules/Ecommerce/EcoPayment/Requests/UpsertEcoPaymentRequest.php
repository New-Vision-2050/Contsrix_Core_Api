<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoPayment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoPayment\DTO\UpsertEcoPaymentDTO;

class UpsertEcoPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'payments' => 'required|array|min:1',
            'payments.*.payment_id' => 'required|string|exists:payments,id',
            'payments.*.is_default' => 'nullable|boolean',
            'payments.*.is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'payments.required' => 'طرق الدفع مطلوبة.',
            'payments.array' => 'طرق الدفع يجب أن تكون مصفوفة.',
            'payments.min' => 'يجب إدخال طريقة دفع واحدة على الأقل.',
            'payments.*.payment_id.required' => 'معرف طريقة الدفع مطلوب.',
            'payments.*.payment_id.exists' => 'طريقة الدفع المحددة غير موجودة.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $payments = $this->input('payments', []);
            $defaultCount = 0;
            
            foreach ($payments as $index => $payment) {
                if (isset($payment['is_default']) && $payment['is_default']) {
                    $defaultCount++;
                }
            }
            
            if ($defaultCount > 1) {
                $validator->errors()->add('payments', 'يمكن أن تكون هناك طريقة دفع افتراضية واحدة فقط.');
            }
        });
    }

    public function createUpsertEcoPaymentDTOs(): array
    {
        $dtos = [];
        $payments = $this->input('payments', []);
        
        foreach ($payments as $payment) {
            $dtos[] = new UpsertEcoPaymentDTO(
                companyId: Uuid::fromString(tenant('id')),
                paymentId: $payment['payment_id'],
                isDefault: (bool) ($payment['is_default'] ?? false),
                isActive: (bool) ($payment['is_active'] ?? true),
            );
        }
        
        return $dtos;
    }
}
