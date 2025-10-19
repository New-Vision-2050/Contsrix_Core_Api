<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoBrand\DTO\Dashboard\CreateEcoBrandDashboardDTO;

class CreateEcoBrandDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],

            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string', 'max:1000'],
            'description.en' => ['nullable', 'string', 'max:1000'],
            
            // Image validation
            'brand_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'], // 5MB max
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => __('ecobrand::validation.name_required'),
            'name.array' => __('ecobrand::validation.name_array'),
            'name.en.string' => __('ecobrand::validation.name_en_string'),
            'name.en.max' => __('ecobrand::validation.name_en_max'),
            'name.ar.required' => __('ecobrand::validation.name_ar_required'),
            'name.ar.string' => __('ecobrand::validation.name_ar_string'),
            'name.ar.max' => __('ecobrand::validation.name_ar_max'),


            'description.array' => __('ecobrand::validation.description_array'),
            'description.ar.required' => __('ecobrand::validation.description_ar_required'),
            'description.ar.string' => __('ecobrand::validation.description_ar_string'),
            'description.ar.max' => __('ecobrand::validation.description_ar_max'),
            'description.en.string' => __('ecobrand::validation.description_en_string'),
            'description.en.max' => __('ecobrand::validation.description_en_max'),
            
            // Image validation messages
            'brand_image.file' => 'صورة العلامة التجارية يجب أن تكون ملف',
            'brand_image.image' => 'صورة العلامة التجارية يجب أن تكون صورة صحيحة',
            'brand_image.mimes' => 'صورة العلامة التجارية يجب أن تكون من نوع: jpeg, png, jpg, gif, webp',
            'brand_image.max' => 'حجم صورة العلامة التجارية يجب ألا يتجاوز 5 ميجابايت',
        ];
    }

    public function createCreateEcoBrandDTO(): CreateEcoBrandDashboardDTO
    {
        $validatedData = $this->validated();

        return new CreateEcoBrandDashboardDTO(
            companyId: Uuid::fromString(tenant("id")),
            name: $validatedData['name'],
            description: $validatedData['description'] ?? null
        );
    }
}
