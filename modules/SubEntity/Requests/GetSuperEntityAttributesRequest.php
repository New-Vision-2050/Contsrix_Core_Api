<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\SubEntity\Rules\ValidSuperEntityId;

class GetSuperEntityAttributesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'super_entity_id' => ['required', 'string', new ValidSuperEntityId()],
        ];
    }
}
