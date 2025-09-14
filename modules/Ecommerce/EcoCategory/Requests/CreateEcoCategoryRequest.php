<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoCategory\DTO\CreateEcoCategoryDTO;

class CreateEcoCategoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Name (multilingual)
            'name' => ['required', 'string'],
            // 'name.ar' => ['required', 'string', 'max:255'],
            // 'name.en' => ['nullable', 'string', 'max:255'],

            'description' => ['required', 'string'],
            // 'description.ar' => ['required', 'string', 'max:1000'],
            // 'description.en' => ['nullable', 'string', 'max:1000'],

            'parent_id' => ['nullable', 'uuid', 'exists:eco_categories,id'],
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
            'name.required' => __('ecocategory::validation.name_required'),
            'name.array' => __('ecocategory::validation.name_array'),
            'name.en.string' => __('ecocategory::validation.name_en_string'),
            'name.en.max' => __('ecocategory::validation.name_en_max'),
            'name.ar.required' => __('ecocategory::validation.name_ar_required'),
            'name.ar.string' => __('ecocategory::validation.name_ar_string'),
            'name.ar.max' => __('ecocategory::validation.name_ar_max'),


            'description.array' => __('ecocategory::validation.description_array'),
            'description.ar.required' => __('ecocategory::validation.description_ar_required'),
            'description.ar.string' => __('ecocategory::validation.description_ar_string'),
            'description.ar.max' => __('ecocategory::validation.description_ar_max'),
            'description.en.string' => __('ecocategory::validation.description_en_string'),
            'description.en.max' => __('ecocategory::validation.description_en_max'),
        ];
    }

    /**
     * Create a DTO from the validated request data.
     */
    public function createCreateEcoCategoryDTO(): CreateEcoCategoryDTO
    {
        $validatedData = $this->validated();

        return new CreateEcoCategoryDTO(
            companyId: Uuid::fromString(tenant("id")),
            name: $validatedData['name'],
            description: $validatedData['description'],
            perentId: $validatedData['parent_id']??null
        );
    }
}
