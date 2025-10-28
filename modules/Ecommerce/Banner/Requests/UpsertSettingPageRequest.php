<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\Banner\DTO\UpsertSettingPageDTO;
use Ramsey\Uuid\Uuid;

class UpsertSettingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|max:255',
            'title_header' => 'nullable|string|max:255',
            'description_header' => 'nullable|string|max:1000',
            'title_footer' => 'nullable|string|max:255',
            'description_footer' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'معرف الشركة مطلوب',
            'company_id.uuid' => 'معرف الشركة يجب أن يكون UUID صحيح',
            'company_id.exists' => 'الشركة غير موجودة',
            'type.required' => 'نوع الصفحة مطلوب',
            'type.string' => 'نوع الصفحة يجب أن يكون نص',
            'type.max' => 'نوع الصفحة يجب ألا يتجاوز 255 حرف',
            'title_header.string' => 'عنوان الهيدر يجب أن يكون نص',
            'title_header.max' => 'عنوان الهيدر يجب ألا يتجاوز 255 حرف',
            'description_header.string' => 'وصف الهيدر يجب أن يكون نص',
            'description_header.max' => 'وصف الهيدر يجب ألا يتجاوز 1000 حرف',
            'title_footer.string' => 'عنوان الفوتر يجب أن يكون نص',
            'title_footer.max' => 'عنوان الفوتر يجب ألا يتجاوز 255 حرف',
            'description_footer.string' => 'وصف الفوتر يجب أن يكون نص',
            'description_footer.max' => 'وصف الفوتر يجب ألا يتجاوز 1000 حرف',
            'is_active.boolean' => 'حالة التفعيل يجب أن تكون صحيح أو خطأ',
        ];
    }

    public function createUpsertSettingPageDTO(): UpsertSettingPageDTO
    {
        return new UpsertSettingPageDTO(
            companyId: Uuid::fromString(tenant("id")),
            type: $this->input('type'),
            titleHeader: $this->input('title_header'),
            descriptionHeader: $this->input('description_header'),
            titleFooter: $this->input('title_footer'),
            descriptionFooter: $this->input('description_footer'),
            isActive: $this->input('is_active', true),
        );
    }
}
