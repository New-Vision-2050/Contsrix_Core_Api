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
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'type' => 'sometimes|string|in:home,discount,new_arrival,contact_us,about_us',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.string' => 'العنوان يجب أن يكون نص',
            'title.max' => 'العنوان يجب ألا يتجاوز 255 حرف',
            'description.string' => 'الوصف يجب أن يكون نص',
            'type.in' => 'نوع الميزة يجب أن يكون أحد القيم التالية: home, discount, new_arrival, contact_us, about_us',
            'is_active.boolean' => 'حالة التفعيل يجب أن تكون صحيح أو خطأ',
        ];
    }

    public function getUpdateData(): array
    {
        $data = [];
        
        if ($this->has('title')) {
            $data['title'] = $this->input('title');
        }
        
        if ($this->has('description')) {
            $data['description'] = $this->input('description');
        }
        
        if ($this->has('type')) {
            $data['type'] = $this->input('type');
        }
        
        if ($this->has('is_active')) {
            $data['is_active'] = $this->input('is_active');
        }
        
        return $data;
    }
}
