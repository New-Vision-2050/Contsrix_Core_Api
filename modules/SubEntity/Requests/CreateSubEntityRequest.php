<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\SubEntity\DTO\CreateSubEntityDTO;
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
            'super_entity' => ['required', 'string', Rule::in($this->getValidSuperEntities())],
            'icon' => 'required|integer|min:0|max:255', // Unsigned tinyint range
            'main_program_id' => 'required|uuid|exists:programs,id',
            'is_active' => 'sometimes|boolean',
            'is_registrable' => 'sometimes|boolean', //TODO registration form is required when this value is true
            'default_attributes' => 'required|array',
            'optional_attributes' => 'sometimes|nullable|array',
        ];
    }


    public function messages(): array
    {
        return [
            'icon.min' => 'Icon code must be between 0-255',
            'icon.max' => 'Icon code must be between 0-255',
            'super_entity.in' => "Invalid entity type. Valid options are: " . implode(',', $this->getValidSuperEntities()),
            'name.unique' => 'This name already exists for the selected super entity type',
        ];
    }

    public function createCreateSubEntityDTO(): CreateSubEntityDTO
    {
        return new CreateSubEntityDTO(
            name: $this->input('name'),
            super_entity: $this->input('super_entity'),
            icon: (int) $this->input('icon'),
            main_program_id: $this->input('main_program_id'),
            is_active: $this->input('is_active', true),
            is_registrable: $this->input('is_registrable', false),
            default_attributes: json_encode($this->input('default_attributes')),
            optional_attributes: $this->filled('optional_attributes')
            ? json_encode($this->input('optional_attributes'))
            : null,
        );
    }

    protected function getValidSuperEntities()
    {
        $superEntityService = app(SuperEntityService::class);
        return $superEntityService->list();
    }
}
