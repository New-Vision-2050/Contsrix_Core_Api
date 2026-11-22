<?php

declare(strict_types=1);

namespace Modules\Forum\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveForumReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'action' => ['required', 'in:delete_message,keep_message'],
            'note' => ['required', 'string', 'max:2000'],
        ];
    }
}
