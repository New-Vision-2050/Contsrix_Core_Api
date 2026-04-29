<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToggleBranchWorkFlowRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', Rule::exists('management_hierarchies', 'id')->where('type', 'branch')],
            'checked'   => 'required|boolean',
        ];
    }
}
