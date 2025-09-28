<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoTermsSettingDashboardDTO;
use Ramsey\Uuid\Uuid;

class UpsertEcoTermsSettingDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'show_terms_text' => 'nullable|boolean',
            'show_privacy_policy' => 'nullable|boolean',
            'show_return_policy' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'show_terms_text.boolean' => 'إظهار نص الشروط والأحكام يجب أن يكون true أو false',
            'show_privacy_policy.boolean' => 'إظهار سياسة الخصوصية يجب أن يكون true أو false',
            'show_return_policy.boolean' => 'إظهار سياسة الاسترجاع يجب أن يكون true أو false',
        ];
    }

    public function createUpsertEcoTermsSettingDTO(): UpsertEcoTermsSettingDashboardDTO
    {
        return new UpsertEcoTermsSettingDashboardDTO(
            company_id: Uuid::fromString(tenant("id")),
            show_terms_text: (int) $this->get('show_terms_text', 1),
            show_privacy_policy: (int) $this->get('show_privacy_policy', 1),
            show_return_policy: (int) $this->get('show_return_policy', 1),
        );
    }
}
