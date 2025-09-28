<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoAppSettingThemeDashboardDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoAppSettingThemeDTO;
use Ramsey\Uuid\Uuid;

class UpsertEcoAppSettingThemeDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'background_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'enable_search' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'معرف الشركة مطلوب',
            'company_id.uuid' => 'معرف الشركة يجب أن يكون UUID صحيح',
            'company_id.exists' => 'الشركة غير موجودة',
            'background_color.regex' => 'لون الخلفية يجب أن يكون بصيغة hex صحيحة (مثال: #1e1b4b)',
            'enable_search.boolean' => 'إظهار البحث يجب أن يكون true أو false',
        ];
    }



    public function createUpsertEcoAppSettingThemeDTO(): UpsertEcoAppSettingThemeDashboardDTO
    {
        return  new UpsertEcoAppSettingThemeDashboardDTO(
            company_id: Uuid::fromString(tenant("id")),
            background_color: $this->get('background_color'),
            enable_search: $this->get('enable_search'),
        );
    }
}
