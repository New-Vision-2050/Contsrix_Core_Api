<?php

declare(strict_types=1);

namespace Modules\Stakeholder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStakeholderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|integer|in:0,1',
        ];
    }
}
