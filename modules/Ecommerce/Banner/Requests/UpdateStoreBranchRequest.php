<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'sometimes|string|in:home,discount,new_arrival,contact_us,about_us',
            'name' => 'sometimes|string|max:255',
            'country_id' => 'sometimes|nullable|exists:countries,id',
            'address' => 'sometimes|nullable|string',
            'phone' => 'sometimes|nullable|string|max:20',
            'email' => 'sometimes|nullable|email|max:255',
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'نوع الفرع يجب أن يكون أحد القيم التالية: main, branch, warehouse, showroom, office',
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

    public function getUpdateData(): array
    {
        $data = [];
        
        if ($this->has('type')) {
            $data['type'] = $this->input('type');
        }
        
        if ($this->has('name')) {
            $data['name'] = $this->input('name');
        }
        
        if ($this->has('country_id')) {
            $data['country_id'] = $this->input('country_id');
        }
        
        if ($this->has('address')) {
            $data['address'] = $this->input('address');
        }
        
        if ($this->has('phone')) {
            $data['phone'] = $this->input('phone');
        }
        
        if ($this->has('email')) {
            $data['email'] = $this->input('email');
        }
        
        if ($this->has('latitude')) {
            $data['latitude'] = $this->input('latitude') ? (float) $this->input('latitude') : null;
        }
        
        if ($this->has('longitude')) {
            $data['longitude'] = $this->input('longitude') ? (float) $this->input('longitude') : null;
        }
        
        if ($this->has('is_active')) {
            $data['is_active'] = $this->input('is_active');
        }
        
        return $data;
    }
}
