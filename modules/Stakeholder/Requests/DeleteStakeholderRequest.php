<?php

declare(strict_types=1);

namespace Modules\Stakeholder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteStakeholderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required|uuid|exists:stakeholders,id',
        ];
    }
}
