<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Package\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\SubscriptionSystem\Package\DTO\CreatePackageDTO;
use Illuminate\Validation\Rule;

class CreatePackageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'currency_id' => 'required|uuid|exists:currencies,id', // Assuming a 'currencies' table
            'billing_cycle' => ['required', Rule::in(['daily', 'monthly', 'yearly'])],

            'trial_period' => 'nullable|integer|min:1',
            'trial_period_type' => ['nullable', 'required_with:trial_period', Rule::in(['day', 'month', 'year'])],

            'is_active' => 'sometimes|boolean',

            'business_type_ids' => 'sometimes|array',
            'business_type_ids.*' => 'required|uuid|exists:business_types,id', // Assuming 'business_types' table

            'country_ids' => 'sometimes|array',
            'country_ids.*' => 'required|exists:countries,id', // Assuming 'countries' table

            'program_system_ids' => 'sometimes|array',
            'program_system_ids.*' => 'required|uuid|exists:program_systems,id', // Assuming 'program_systems' table
        ];
    }

    public function createCreatePackageDTO(): CreatePackageDTO
    {
        return new CreatePackageDTO(
            name: $this->get('name'),
            price: (float) $this->get('price'),
            currencyId: $this->get('currency_id'),
            billingCycle: $this->get('billing_cycle'),
            trialPeriod: $this->get('trial_period'),
            trialPeriodType: $this->get('trial_period_type'),
            isActive: $this->boolean('is_active', true),
            businessTypeIds: $this->get('business_type_ids', []),
            countryIds: $this->get('country_ids', []),
            programSystemIds: $this->get('program_system_ids', [])
        );
    }
}
