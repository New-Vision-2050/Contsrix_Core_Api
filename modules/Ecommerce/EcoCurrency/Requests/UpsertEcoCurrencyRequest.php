<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCurrency\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoCurrency\DTO\UpsertEcoCurrencyDTO;

class UpsertEcoCurrencyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'currencies' => 'required|array|min:1',
            'currencies.*.currency_id' => 'required|string|exists:currencies,id',
            'currencies.*.is_default' => 'nullable|boolean',
            'currencies.*.is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'currencies.required' => 'يجب اختيار عملة واحدة على الأقل.',
            'currencies.array' => 'العملات يجب أن تكون مصفوفة.',
            'currencies.min' => 'يجب اختيار عملة واحدة على الأقل.',
            'currencies.*.currency_id.required' => 'معرف العملة مطلوب.',
            'currencies.*.currency_id.exists' => 'العملة المحددة غير موجودة.',
            'currencies.*.is_default.boolean' => 'حقل العملة الافتراضية يجب أن يكون صحيح أو خطأ.',
            'currencies.*.is_active.boolean' => 'حقل تفعيل العملة يجب أن يكون صحيح أو خطأ.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $currencies = $this->get('currencies', []);
            $defaultCount = 0;
            
            foreach ($currencies as $currency) {
                if (isset($currency['is_default']) && $currency['is_default']) {
                    $defaultCount++;
                }
            }
            
            if ($defaultCount > 1) {
                $validator->errors()->add('currencies', 'يمكن تحديد عملة افتراضية واحدة فقط.');
            }
        });
    }

    public function createUpsertEcoCurrencyDTO(): UpsertEcoCurrencyDTO
    {
        return new UpsertEcoCurrencyDTO(
            companyId: Uuid::fromString(tenant("id")),
            currencies: $this->get('currencies', []),
        );
    }
}
