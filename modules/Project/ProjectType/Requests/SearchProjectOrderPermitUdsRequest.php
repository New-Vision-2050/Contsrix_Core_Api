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
            'name' => ['required', 'string'],
            'order_permit_id' => ['required', 'integer', 'exists:order_permit,id'],
        ];
    }

    public function name(): string
    {
        return trim((string) $this->validated('name'));
    }

    public function orderPermitId(): int
    {
        return (int) $this->validated('order_permit_id');
    }
}
