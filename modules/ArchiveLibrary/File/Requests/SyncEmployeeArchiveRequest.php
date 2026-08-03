<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncEmployeeArchiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'nullable|uuid|exists:companies,id',
            'employee_global_ids' => 'nullable|array',
            'employee_global_ids.*' => 'required|uuid',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'required|uuid',
            'dry_run' => 'sometimes|boolean',
        ];
    }

    public function getCompanyId(): ?string
    {
        if ($this->filled('company_id')) {
            return (string) $this->input('company_id');
        }

        if (function_exists('tenancy') && tenancy()->initialized && tenant('id')) {
            return (string) tenant('id');
        }

        return auth()->user()?->company_id ? (string) auth()->user()->company_id : null;
    }

    public function getEmployeeGlobalIds(): ?array
    {
        $ids = array_merge(
            (array) $this->input('employee_global_ids', []),
            (array) $this->input('employee_ids', [])
        );

        $ids = array_values(array_unique(array_filter(
            array_map(fn ($id) => is_string($id) ? trim($id) : null, $ids)
        )));

        return $ids === [] ? null : $ids;
    }

    public function isDryRun(): bool
    {
        return $this->boolean('dry_run');
    }
}
