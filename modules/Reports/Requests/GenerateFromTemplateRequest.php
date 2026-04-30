<?php

declare(strict_types=1);

namespace Modules\Reports\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/v1/reports/templates/{id}/generate — spawns a Report from a
 * saved template with optional per-run overrides (year/month/name, etc.).
 */
class GenerateFromTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'nullable|array',
            'name.ar'     => 'nullable|string|max:255',
            'name.en'     => 'nullable|string|max:255',

            // Optional per-run period overrides (applied on top of the stored template).
            'year'        => 'nullable|integer|min:2000|max:2100',
            'month'       => 'nullable|integer|min:1|max:12',
            'week'        => 'nullable|integer|min:1|max:53',
            'quarter'     => 'nullable|integer|min:1|max:4',
        ];
    }
}
