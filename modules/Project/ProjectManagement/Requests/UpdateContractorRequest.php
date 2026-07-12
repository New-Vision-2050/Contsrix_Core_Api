<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'tax_card' => ['nullable', 'string', 'max:255'],
            'commercial_register' => ['nullable', 'string', 'max:255'],
            'activity' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'country_id' => ['nullable', 'uuid', 'exists:countries,id'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'project_contractor_id' => ['nullable', 'string', 'max:255'],
            'project_manager_name' => ['nullable', 'string', 'max:255'],
            'project_manager_phone' => ['nullable', 'string', 'max:255'],
            'project_manager_nationality' => ['nullable', 'string', 'max:255'],
            'project_manager_email' => ['nullable', 'email', 'max:255'],
            'representatives' => ['nullable', 'array'],
            'representatives.*.name' => ['required_with:representatives.*.name', 'string', 'max:255'],
            'representatives.*.mobile' => ['nullable', 'string', 'max:255'],
            'representatives.*.nationality' => ['nullable', 'string', 'max:255'],
        ];
    }
}
