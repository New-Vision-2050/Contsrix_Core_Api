<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\Banner\DTO\CreateFeatureDTO;
use Ramsey\Uuid\Uuid;

class CreateFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'setting_page_id' => 'nullable|uuid|exists:setting_pages,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'معرف الشركة مطلوب',
            'company_id.uuid' => 'معرف الشركة يجب أن يكون UUID صحيح',
            'company_id.exists' => 'الشركة غير موجودة',
            'setting_page_id.uuid' => 'معرف صفحة الإعدادات يجب أن يكون UUID صحيح',
            'setting_page_id.exists' => 'صفحة الإعدادات غير موجودة',
            'title.required' => 'العنوان مطلوب',
            'title.string' => 'العنوان يجب أن يكون نص',
            'title.max' => 'العنوان يجب ألا يتجاوز 255 حرف',
            'description.required' => 'الوصف مطلوب',
            'description.string' => 'الوصف يجب أن يكون نص',
            'is_active.boolean' => 'حالة التفعيل يجب أن تكون صحيح أو خطأ',
        ];
    }

    public function createCreateFeatureDTO(): CreateFeatureDTO
    {
        return new CreateFeatureDTO(
            companyId: Uuid::fromString(tenant("id")),
            settingPageId: $this->input('setting_page_id') ? Uuid::fromString($this->input('setting_page_id')) : null,
            title: $this->input('title'),
            description: $this->input('description'),
            isActive: $this->input('is_active', true),
        );
    }
}
