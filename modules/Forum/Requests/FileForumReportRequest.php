<?php

declare(strict_types=1);

namespace Modules\Forum\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileForumReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
