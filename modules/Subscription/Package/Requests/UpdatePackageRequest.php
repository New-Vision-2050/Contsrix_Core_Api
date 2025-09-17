<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Subscription\Enums\PeriodUnitEnum;
use Modules\Subscription\Package\DTO\UpdatePackageDTO;
use Ramsey\Uuid\Uuid;
use Modules\Subscription\Package\Commands\UpdatePackageCommand;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Prevent updating main packages
        $packageId = $this->route('id');
        $package = \Modules\Subscription\Package\Models\Package::find($packageId);

        if ($package && $package->is_main_package) {
            return false;
        }

        return true;
    }



    public function rules(): array
    {
        $packageId = $this->route('id');

        // Get the package to find its company_access_program_id
        $package = \Modules\Subscription\Package\Models\Package::findOrFail($packageId);
        $companyAccessProgramId = $package->company_access_program_id;

        return [
            'name' => ['required', 'string',
                Rule::unique('packages', 'name')
                ->where('company_access_program_id', $companyAccessProgramId)
                ->ignore($packageId),
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

    public function createUpdatePackageDTO(): UpdatePackageDTO
    {
        return new UpdatePackageDTO(
            packageId: $this->route('id'),
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

    public function createUpdatePackageCommand(): UpdatePackageCommand
    {
        return new UpdatePackageCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
