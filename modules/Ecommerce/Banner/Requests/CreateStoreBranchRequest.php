<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\Banner\DTO\CreateStoreBranchDTO;
use Ramsey\Uuid\Uuid;

class CreateStoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:home,discount,new_arrival,contact_us,about_us',
            'name' => 'required|string|max:255',
            'country_id' => 'nullable|exists:countries,id',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'نوع الفرع مطلوب',
            'type.in' => 'نوع الفرع يجب أن يكون أحد القيم التالية: main, branch, warehouse, showroom, office',
            'name.required' => 'اسم الفرع مطلوب',
            'name.string' => 'اسم الفرع يجب أن يكون نص',
            'name.max' => 'اسم الفرع يجب ألا يتجاوز 255 حرف',
            'country_id.uuid' => 'معرف الدولة يجب أن يكون UUID صحيح',
            'country_id.exists' => 'الدولة المحددة غير موجودة',
            'address.string' => 'العنوان يجب أن يكون نص',
            'phone.string' => 'رقم الهاتف يجب أن يكون نص',
            'phone.max' => 'رقم الهاتف يجب ألا يتجاوز 20 حرف',
            'email.email' => 'البريد الإلكتروني يجب أن يكون صحيح',
            'email.max' => 'البريد الإلكتروني يجب ألا يتجاوز 255 حرف',
            'latitude.numeric' => 'خط العرض يجب أن يكون رقم',
            'latitude.between' => 'خط العرض يجب أن يكون بين -90 و 90',
            'longitude.numeric' => 'خط الطول يجب أن يكون رقم',
            'longitude.between' => 'خط الطول يجب أن يكون بين -180 و 180',
            'is_active.boolean' => 'حالة التفعيل يجب أن تكون صحيح أو خطأ',
        ];
    }

    public function createCreateStoreBranchDTO(): CreateStoreBranchDTO
    {
        return new CreateStoreBranchDTO(
            companyId: Uuid::fromString(tenant("id")),
            type: $this->input('type'),
            name: $this->input('name'),
            countryId: $this->input('country_id'),
            address: $this->input('address'),
            phone: $this->input('phone'),
            email: $this->input('email'),
            latitude: $this->input('latitude') ? (float) $this->input('latitude') : null,
            longitude: $this->input('longitude') ? (float) $this->input('longitude') : null,
            isActive: $this->input('is_active', true),
        );
    }
}
