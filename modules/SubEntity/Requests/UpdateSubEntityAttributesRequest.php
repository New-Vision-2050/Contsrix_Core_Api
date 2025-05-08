<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Ramsey\Uuid\Uuid;
use Illuminate\Foundation\Http\FormRequest;
use Modules\SubEntity\Commands\UpdateSubEntityAttributesCommand;

class UpdateSubEntityAttributesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'default_attributes' => 'required|array', //ToDo Validate against super entity attributes
            'optional_attributes' => 'sometimes|nullable|array', //Validate against super entity attributes
        ];
    }

    public function createUpdateSubEntityAttributesCommand(): UpdateSubEntityAttributesCommand
    {
        return new UpdateSubEntityAttributesCommand(
            id: Uuid::fromString($this->route('id')),
            default_attributes: json_encode($this->input('default_attributes')),
            optional_attributes: $this->filled('optional_attributes')
            ? json_encode($this->input('optional_attributes'))
            : null,
        );
    }
}
