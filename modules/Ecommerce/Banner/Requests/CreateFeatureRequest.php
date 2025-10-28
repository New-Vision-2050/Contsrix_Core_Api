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
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|string|in:home,discount,new_arrival,contact_us, about_us',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'العنوان مطلوب',
            'title.string' => 'العنوان يجب أن يكون نص',
            'title.max' => 'العنوان يجب ألا يتجاوز 255 حرف',
            'description.required' => 'الوصف مطلوب',
            'description.string' => 'الوصف يجب أن يكون نص',
            'type.required' => 'نوع الميزة مطلوب',
            'type.in' => 'نوع الميزة يجب أن يكون أحد القيم التالية: home, discount, new_arrival, contact_us, about_us',
            'is_active.boolean' => 'حالة التفعيل يجب أن تكون صحيح أو خطأ',
        ];
    }

    public function createCreateFeatureDTO(): CreateFeatureDTO
    {
        return new CreateFeatureDTO(
            companyId: Uuid::fromString(tenant("id")),
            title: $this->input('title'),
            description: $this->input('description'),
            type: $this->input('type'),
            isActive: $this->input('is_active', true),
        );
    }
}
