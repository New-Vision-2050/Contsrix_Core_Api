<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecidePenaltyExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // use_exception → spend one monthly exception and waive the day's penalty
            // accept_penalty → acknowledge the penalty; it stands
            'action' => ['required', 'string', 'in:use_exception,accept_penalty'],
        ];
    }
}
