<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Ramsey\Uuid\Uuid;
use Modules\SubEntity\Models\SubEntity;
use Illuminate\Foundation\Http\FormRequest;

class GetSubEntityRequest extends FormRequest
{
    public function rules(): array
    {
        SubEntity::findOrFail($this->route('id'));
        return [];
    }
}
