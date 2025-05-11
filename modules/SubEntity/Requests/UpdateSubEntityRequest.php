<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Ramsey\Uuid\Uuid;
use Illuminate\Validation\Rule;
use Modules\SubEntity\Models\SubEntity;
use Illuminate\Foundation\Http\FormRequest;
use Modules\SubEntity\Commands\UpdateSubEntityCommand;

class UpdateSubEntityRequest extends FormRequest
{
    public function rules(): array
    {
        $subEntity = SubEntity::findOrFail($this->route('id'), ['id', 'super_entity']);

        return [
                'name' => [
                    'required',
                    'string',
                    Rule::unique('sub_entities')
                    ->where(function ($query)use($subEntity) {
                        return $query->where('super_entity', $subEntity->super_entity);
                    })->ignore($this->route('id'))
            ],
            'icon' => 'required|integer|min:0|max:255',
            'main_program_id' => 'required|uuid|exists:programs,id',
            'is_active' => 'required|boolean',
            'is_registrable' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'icon.min' => 'Icon code must be between 0-255',
            'icon.max' => 'Icon code must be between 0-255',
            'name.unique' => 'This name already exists for the selected super entity type',
        ];
    }

    public function createUpdateSubEntityCommand(): UpdateSubEntityCommand
    {
        return new UpdateSubEntityCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            icon: $this->get('icon'),
            mainProgramId: $this->get('main_program_id'),
            isActive: (bool) $this->get('is_active'),
            isRegistrable: (bool) $this->get('is_registrable'),

        );
    }
}
