<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchProjectOrderPermitUdsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function search(): string
    {
        return trim((string) ($this->validated('search') ?? ''));
    }

    public function limit(): int
    {
        return (int) ($this->validated('limit') ?? 20);
    }
}
