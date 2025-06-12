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
            'is_registrable' => 'required|boolean',
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

    public function createUpdateSubEntityCommand(): UpdateSubEntityCommand
    {
        return new UpdateSubEntityCommand(
            id: Uuid::fromString($this->route('id')),
            isRegistrable: (bool) $this->get('is_registrable'),
            childrenAllowedRegistrationForms: $this->get('children_allowed_registration_forms'),
        );
    }
}
