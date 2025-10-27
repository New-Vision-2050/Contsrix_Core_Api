<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class UpdateFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'setting_page_id' => 'sometimes|nullable|uuid|exists:setting_pages,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'setting_page_id.uuid' => 'معرف صفحة الإعدادات يجب أن يكون UUID صحيح',
            'setting_page_id.exists' => 'صفحة الإعدادات غير موجودة',
            'title.string' => 'العنوان يجب أن يكون نص',
            'title.max' => 'العنوان يجب ألا يتجاوز 255 حرف',
            'description.string' => 'الوصف يجب أن يكون نص',
            'is_active.boolean' => 'حالة التفعيل يجب أن تكون صحيح أو خطأ',
        ];
    }

    public function getUpdateData(): array
    {
        $data = [];
        
        if ($this->has('setting_page_id')) {
            $data['setting_page_id'] = $this->input('setting_page_id') ? 
                Uuid::fromString($this->input('setting_page_id'))->toString() : null;
        }
        
        if ($this->has('title')) {
            $data['title'] = $this->input('title');
        }
        
        if ($this->has('description')) {
            $data['description'] = $this->input('description');
        }
        
        if ($this->has('is_active')) {
            $data['is_active'] = $this->input('is_active');
        }
        
        return $data;
    }
}
