<?php declare(strict_types=1);

namespace Modules\Subscription\Package\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Subscription\Enums\PeriodUnitEnum;
use Modules\Subscription\Package\DTO\CreatePackageDTO;

class CreatePackageRequest extends FormRequest
{
    public function rules(): array
    {
        $companyAccessProgramId = $this->company_access_program_id;

        return [
            'company_access_program_id' => ['required', 'uuid', Rule::exists('company_access_programs', 'id')],

            'name' => ['required', 'string',
                Rule::unique('packages', 'name')
                ->where('company_access_program_id', $companyAccessProgramId),
            ],

            'price' => ['required', 'numeric', 'min:0'],

            'currency' => [
                'required',
                'string',
                Rule::exists('countries', 'currency'),
            ],

            'subscription_period' => ['required', 'integer', 'min:1'],
            'subscription_period_unit' => ['required', new Enum(PeriodUnitEnum::class)],

            'trial_period' => ['nullable', 'integer', 'min:0'],
            'trial_period_unit' => ['nullable', new Enum(PeriodUnitEnum::class)],

            'countries' => ['nullable', 'array'],
            'countries.*' => [
                'integer',
                Rule::exists('company_access_program_country', 'country_id')->where('company_access_program_id', $companyAccessProgramId),
            ],

            'company_fields' => ['nullable', 'array'],
            'company_fields.*' => [
                'uuid',
                Rule::exists('company_access_program_field', 'company_field_id')->where('company_access_program_id', $companyAccessProgramId),
            ],

            'company_types' => ['nullable', 'array'],
            'company_types.*' => [
                'uuid',
                Rule::exists('company_access_program_type', 'company_type_id')->where('company_access_program_id', $companyAccessProgramId),
            ],
        ];
    }

    public function createCreatePackageDTO(): CreatePackageDTO
    {
        return new CreatePackageDTO(
            companyAccessProgramId: $this->input('company_access_program_id'),
            name: $this->input('name'),
            price: (float) $this->input('price'),
            currency: $this->input('currency'),
            subscriptionPeriod: (int) $this->input('subscription_period'),
            subscriptionPeriodUnit: PeriodUnitEnum::from($this->input('subscription_period_unit')),
            trialPeriod: $this->filled('trial_period') ? (int) $this->input('trial_period') : null,
            trialPeriodUnit: $this->filled('trial_period_unit') ? PeriodUnitEnum::from($this->input('trial_period_unit')) : null,
            countries: $this->input('countries', []),
            companyFields: $this->input('company_fields', []),
            companyTypes: $this->input('company_types', []),
        );
    }
}
