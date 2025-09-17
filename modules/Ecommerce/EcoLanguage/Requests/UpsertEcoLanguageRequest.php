<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoLanguage\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoLanguage\DTO\CreateEcoLanguageDTO;

class UpsertEcoLanguageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'languages' => 'required|array|min:1',
            'languages.*.language_id' => 'required|string|exists:languages,id',
            'languages.*.is_default' => 'nullable|boolean',
            'languages.*.is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'languages.required' => 'يجب اختيار لغة واحدة على الأقل.',
            'languages.array' => 'اللغات يجب أن تكون مصفوفة.',
            'languages.min' => 'يجب اختيار لغة واحدة على الأقل.',
            'languages.*.language_id.required' => 'معرف اللغة مطلوب.',
            'languages.*.language_id.exists' => 'اللغة المحددة غير موجودة.',
            'languages.*.is_default.boolean' => 'حقل اللغة الافتراضية يجب أن يكون صحيح أو خطأ.',
            'languages.*.is_active.boolean' => 'حقل تفعيل اللغة يجب أن يكون صحيح أو خطأ.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $languages = $this->get('languages', []);
            $defaultCount = 0;

            foreach ($languages as $language) {
                if (isset($language['is_default']) && $language['is_default']) {
                    $defaultCount++;
                }
            }

            if ($defaultCount > 1) {
                $validator->errors()->add('languages', 'يمكن تحديد لغة افتراضية واحدة فقط.');
            }
        });
    }

    public function createUpsertEcoLanguageDTO(): CreateEcoLanguageDTO
    {
        return new CreateEcoLanguageDTO(
            companyId: Uuid::fromString(tenant("id")),
            languages: $this->get('languages', []),
        );
    }
}
