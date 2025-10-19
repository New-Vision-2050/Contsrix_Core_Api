<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoCategory\Commands\Dashboard\UpdateEcoCategoryDashboardCommand;

class UpdateEcoCategoryDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [

            'name' => ['nullable', 'array'],
            'name.ar' => ['nullable', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],

            'parent_id' => ['nullable', 'uuid', 'exists:eco_categories,id'],
            'priority' => ['nullable', 'integer', 'min:0'],
            
            // Image validation
            'category_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'], // 5MB max
        ];
    }

    public function messages(): array
    {
        return [
            'name.array' => 'اسم الفئة يجب أن يكون مصفوفة تحتوي على اللغات',
            'name.ar.string' => 'اسم الفئة باللغة العربية يجب أن يكون نص',
            'name.ar.max' => 'اسم الفئة باللغة العربية يجب ألا يتجاوز 255 حرف',
            'name.en.string' => 'اسم الفئة باللغة الإنجليزية يجب أن يكون نص',
            'name.en.max' => 'اسم الفئة باللغة الإنجليزية يجب ألا يتجاوز 255 حرف',

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

    public function createUpdateEcoCategoryCommand(): UpdateEcoCategoryDashboardCommand
    {
        $validatedData = $this->validated();

        return new UpdateEcoCategoryDashboardCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            perentId: $this->get('parent_id'),
            priority: $this->get('priority'),
        );
    }
}
