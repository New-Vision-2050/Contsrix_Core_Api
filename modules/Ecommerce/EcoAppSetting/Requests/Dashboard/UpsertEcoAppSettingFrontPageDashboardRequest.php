<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoAppSettingFrontPageDashboardDTO;
use Ramsey\Uuid\Uuid;

class UpsertEcoAppSettingFrontPageDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'show_logo_on_first_page' => 'nullable|boolean',
            'show_logo_on_front_page' => 'nullable|boolean',
            'count_photos' => 'nullable|integer|min:1|max:8',
            'logo' => 'nullable|array',
            'logo.*' => 'file|mimes:jpg,jpeg,png,svg'
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'معرف الشركة مطلوب',
            'company_id.uuid' => 'معرف الشركة يجب أن يكون UUID صحيح',
            'company_id.exists' => 'الشركة غير موجودة',
            'show_logo_on_first_page.boolean' => 'ظهور الشعار في التطبيق يجب أن يكون true أو false',
            'show_logo_on_front_page.boolean' => 'ظهور الشعار في التطبيق يجب أن يكون true أو false',
            'count_photos.integer' => 'عدد الصور يجب أن يكون رقم صحيح',
            'count_photos.min' => 'عدد الصور يجب أن يكون على الأقل 1',
            'count_photos.max' => 'عدد الصور يجب أن يكون 8 كحد أقصى',
            'logo.array' => 'الشعار يجب أن يكون مصفوفة من الملفات',
            'logo.*.file' => 'كل عنصر في الشعار يجب أن يكون ملفًا',
            'logo.*.mimes' => 'كل ملف في الشعار يجب أن يكون من نوع jpg, jpeg, png, أو svg',
        ];
    }

    public function createUpsertEcoAppSettingFrontPageDTO(): UpsertEcoAppSettingFrontPageDashboardDTO
    {
        return new UpsertEcoAppSettingFrontPageDashboardDTO(
            company_id: Uuid::fromString(tenant("id")),
            show_logo_on_first_page: (int)$this->get('show_logo_on_first_page'),
            show_logo_on_front_page: (int)$this->get('show_logo_on_front_page'),
            count_photos: (int) $this->validated('count_photos', 1),
            logo: $this->hasFile('logo') ? $this->file('logo') : [],
        );
    }
}
