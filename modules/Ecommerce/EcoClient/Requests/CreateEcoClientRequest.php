<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoClient\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoClient\DTO\CreateEcoClientDTO;
use Illuminate\Validation\Rule;
class CreateEcoClientRequest extends FormRequest
{
    public function rules(): array
    {
        $companyId = tenant("id");
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                // Modified unique rule: unique to eco_clients table, email column,
                // BUT only where company_id matches the current tenant's ID.
                Rule::unique('eco_clients', 'email')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                }),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'profile_image' => ['nullable', 'image', 'max:2048'],
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => __('ecoclient::validation.name_required'),
            'name.string' => __('ecoclient::validation.name_string'),
            'name.max' => __('ecoclient::validation.name_max'),
            'email.required' => __('ecoclient::validation.email_required'),
            'email.string' => __('ecoclient::validation.email_string'),
            'email.email' => __('ecoclient::validation.email_email'),
            'email.max' => __('ecoclient::validation.email_max'),
            'email.unique' => __('ecoclient::validation.email_unique'),
            'password.required' => __('ecoclient::validation.password_required'),
            'password.string' => __('ecoclient::validation.password_string'),
            'password.min' => __('ecoclient::validation.password_min'),
            'password.confirmed' => __('ecoclient::validation.password_confirmed'),
            'phone_code.string' => __('ecoclient::validation.phone_code_string'),
            'phone_code.max' => __('ecoclient::validation.phone_code_max'),
            'phone.string' => __('ecoclient::validation.phone_string'),
            'phone.max' => __('ecoclient::validation.phone_max'),
        ];
    }

    public function createCreateEcoClientDTO(): CreateEcoClientDTO
    {
        $validatedData = $this->validated();
        return new CreateEcoClientDTO(
            companyId: Uuid::fromString(tenant("id")),
            name: $validatedData['name'],
            email: $validatedData['email'],
            password: $validatedData['password'],
            phoneCode: $validatedData['phone_code'] ?? null,
            phone: $validatedData['phone'] ?? null,
            profileImage: $this->file('profile_image')
        );
    }
}
