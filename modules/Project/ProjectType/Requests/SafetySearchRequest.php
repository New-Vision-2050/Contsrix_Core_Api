<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SafetySearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:255'],
        ];
    }

    public function queryString(): string
    {
        return (string) $this->validated('q');
    }
}
