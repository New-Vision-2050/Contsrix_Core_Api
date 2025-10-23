<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoCategory\DTO\Dashboard\CreateEcoCategoryDashboardDTO;

class CreateEcoCategoryDashboardRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],

            'parent_id' => ['nullable', 'uuid', 'exists:eco_categories,id'],
            'priority' => ['nullable', 'integer', 'min:0'],
            
            // Image validation
            'category_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'], // 5MB max
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Name validation messages
            'name.required' => 'اسم الفئة مطلوب',
            'name.array' => 'اسم الفئة يجب أن يكون مصفوفة تحتوي على اللغات',
            'name.ar.required' => 'اسم الفئة باللغة العربية مطلوب',
            'name.ar.string' => 'اسم الفئة باللغة العربية يجب أن يكون نص',
            'name.ar.max' => 'اسم الفئة باللغة العربية يجب ألا يتجاوز 255 حرف',
            'name.en.string' => 'اسم الفئة باللغة الإنجليزية يجب أن يكون نص',
            'name.en.max' => 'اسم الفئة باللغة الإنجليزية يجب ألا يتجاوز 255 حرف',

            // Other validation messages
            'parent_id.uuid' => 'معرف الفئة الأب يجب أن يكون UUID صحيح',
            'parent_id.exists' => 'الفئة الأب المحددة غير موجودة',
            'priority.integer' => 'الأولوية يجب أن تكون رقم صحيح',
            'priority.min' => 'الأولوية يجب أن تكون 0 أو أكثر',
            
            // Image validation messages
            'category_image.file' => 'صورة الفئة يجب أن تكون ملف',
            'category_image.image' => 'صورة الفئة يجب أن تكون صورة صحيحة',
            'category_image.mimes' => 'صورة الفئة يجب أن تكون من نوع: jpeg, png, jpg, gif, webp',
            'category_image.max' => 'حجم صورة الفئة يجب ألا يتجاوز 5 ميجابايت',
        ];
    }

    /**
     * Create a DTO from the validated request data.
     */
    public function createCreateEcoCategoryDTO(): CreateEcoCategoryDashboardDTO
    {
        $validatedData = $this->validated();

        return new CreateEcoCategoryDashboardDTO(
            companyId: Uuid::fromString(tenant("id")),
            name: $validatedData['name'],
            parentId: isset($validatedData['parent_id']) ? Uuid::fromString($validatedData['parent_id']) : null,
            priority: (int)($validatedData['priority'] ?? 0)
        );
    }
}
