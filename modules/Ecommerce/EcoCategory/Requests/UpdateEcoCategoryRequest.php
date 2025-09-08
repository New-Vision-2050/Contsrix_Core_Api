<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoCategory\Commands\UpdateEcoCategoryCommand;
use Modules\Ecommerce\EcoCategory\Handlers\UpdateEcoCategoryHandler;

class UpdateEcoCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            // 'name.ar' => ['required', 'string', 'max:255'],
            // 'name.en' => ['nullable', 'string', 'max:255'],

            'description' => ['required', 'string'],
            // 'description.ar' => ['required', 'string', 'max:1000'],
            // 'description.en' => ['nullable', 'string', 'max:1000'],

            'parent_id' => ['nullable', 'uuid', 'exists:eco_categories,id'],
        ];
    }

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

             'parent_id' => ['nullable', 'uuid', 'exists:eco_categories,id'],
        ];
    }

    public function createUpdateEcoCategoryCommand(): UpdateEcoCategoryCommand
    {
        $validatedData = $this->validated();

        return new UpdateEcoCategoryCommand(
            id: Uuid::fromString($this->route('id')),
            name: $validatedData['name'],
            description: $validatedData['description'],
            perentId: $validatedData['parent_id']
        );
    }
}
