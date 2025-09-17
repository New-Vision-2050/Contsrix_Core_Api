<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoInstallment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoInstallment\DTO\UpsertEcoInstallmentDTO;

class UpsertEcoInstallmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'installments' => 'required|array|min:1',
            'installments.*.installment_id' => 'required|string|exists:installments,id',
            'installments.*.is_default' => 'nullable|boolean',
            'installments.*.is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'installments.required' => 'الأقساط مطلوبة.',
            'installments.array' => 'الأقساط يجب أن تكون مصفوفة.',
            'installments.min' => 'يجب إدخال قسط واحد على الأقل.',
            'installments.*.installment_id.required' => 'معرف القسط مطلوب.',
            'installments.*.installment_id.exists' => 'القسط المحدد غير موجود.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $installments = $this->input('installments', []);
            $defaultCount = 0;
            
            foreach ($installments as $index => $installment) {
                if (isset($installment['is_default']) && $installment['is_default']) {
                    $defaultCount++;
                }
            }
            
            if ($defaultCount > 1) {
                $validator->errors()->add('installments', 'يمكن أن يكون هناك قسط افتراضي واحد فقط.');
            }
        });
    }

    public function createUpsertEcoInstallmentDTOs(): array
    {
        $dtos = [];
        $installments = $this->input('installments', []);
        
        foreach ($installments as $installment) {
            $dtos[] = new UpsertEcoInstallmentDTO(
                companyId: Uuid::fromString(tenant('id')),
                installmentId: $installment['installment_id'],
                isDefault: (bool) ($installment['is_default'] ?? false),
                isActive: (bool) ($installment['is_active'] ?? true),
            );
        }
        
        return $dtos;
    }
}
