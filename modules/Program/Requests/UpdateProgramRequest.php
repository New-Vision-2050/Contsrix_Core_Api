<?php

declare(strict_types=1);

namespace Modules\Program\Requests;

use Ramsey\Uuid\Uuid;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Program\Commands\UpdateProgramCommand;
use Modules\Program\Handlers\UpdateProgramHandler;

class UpdateProgramRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', Rule::unique('programs', 'name->en')->ignore($this->route('id'))],
            'name_ar' => ['required', 'string', Rule::unique('programs', 'name->ar')->ignore($this->route('id'))],
            'parent_id' => 'nullable|exists:programs,id'
        ];
    }

    public function createUpdateProgramCommand(): UpdateProgramCommand
    {
        return new UpdateProgramCommand(
            id: Uuid::fromString($this->route('id')),
            name: [
                'en' => $this->get('name_en'),
                'ar' => $this->get('name_ar'),
            ],
            parentId: $this->get('parent_id')
        );
    }
}
