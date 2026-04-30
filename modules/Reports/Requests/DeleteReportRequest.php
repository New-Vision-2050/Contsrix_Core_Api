<?php

declare(strict_types=1);

namespace Modules\Reports\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
