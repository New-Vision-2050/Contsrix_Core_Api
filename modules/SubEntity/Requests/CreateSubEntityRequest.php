<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\SubEntity\DTO\CreateSubEntityDTO;
use Modules\SubEntity\Rules\ValidSuperEntityId;
use Modules\SubEntity\Services\SuperEntityService;

class CreateSubEntityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sub_entities')
                    ->where(function ($query) {
                        return $query->where('super_entity', $this->input('super_entity'));
                    })
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('sub_entities', 'slug'),
            ],
            'super_entity' => ['required', 'string', new ValidSuperEntityId()],
            'icon' => 'required|string|max:255',
            'main_program_id' => 'required|uuid|exists:programs,id',
            'is_active' => 'sometimes|boolean',
            'is_registrable' => 'sometimes|boolean',
            'default_attributes' => 'required|array',
            'default_attributes.*' => [Rule::In($this->getValidSuperEntityAttributes())],
            'optional_attributes' => 'sometimes|nullable|array',
            'optional_attributes.*' => [Rule::In($this->getValidSuperEntityAttributes())],
            'registration_form_id' => 'required|exists:registration_forms,id',
            'children_allowed_registration_forms' => 'nullable|array',
            'children_allowed_registration_forms.*' => 'required|distinct|exists:registration_forms,id',
        ];
    }


    public function messages(): array
    {
        return [
            'icon.max' => 'The icon name must not exceed 255 characters.',
            'name.unique' => 'This name already exists for the selected super entity type',
            'slug.regex' => 'The slug must contain only lowercase letters, numbers, and hyphens, and cannot start or end with a hyphen.',
            'slug.unique' => 'This slug is already taken.',
        ];
    }

    public function createCreateSubEntityDTO(): CreateSubEntityDTO
    {
        return new CreateSubEntityDTO(
            name: $this->input('name'),
            slug: $this->input('slug'),
            super_entity: $this->input('super_entity'),
            icon: $this->input('icon'),
            main_program_id: $this->input('main_program_id'),
            is_active: $this->input('is_active', true),
            is_registrable: $this->input('is_registrable', false),
            default_attributes: $this->input('default_attributes'),
            optional_attributes: $this->input('optional_attributes'),
            registrationFormId: $this->input('registration_form_id'),
            childrenAllowedRegistrationForms: $this->input('children_allowed_registration_forms'),
        );
    }

    protected function getValidSuperEntityAttributes()
    {
        $superEntityId = $this->get('super_entity');
        if (empty($superEntityId)) {
            return [];
        }

        $superEntityService = app(SuperEntityService::class);
        $superEntityModel = $superEntityService->getModelForId($superEntityId);

        return $superEntityModel ? $superEntityModel::getSubEntitiesAvailableAttributes() : [];
    }
}
