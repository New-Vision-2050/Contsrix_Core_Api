<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use Modules\SubscriptionSystem\Subscription\DTO\CreatePackageDTO;
use Modules\SubscriptionSystem\Subscription\DTO\PackageModuleDTO;
use Modules\SubscriptionSystem\Subscription\DTO\PackageFeatureDTO;
use Modules\SubscriptionSystem\Subscription\Rules\ValidModuleFeatures;
use Modules\SubscriptionSystem\Subscription\Enums\FeatureLimitTypeEnum;
use Modules\SubscriptionSystem\Subscription\Enums\PackageBillingCycleEnum;

class CreatePackageRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'array'],
            'name.en' => [
                'required',
                'string',
                Rule::unique('packages', 'name->en'),
            ],
            'name.ar' => [
                'required',
                'string',
                Rule::unique('packages', 'name->ar'),
            ],

            'price' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'billing_cycle' => ['required', new Enum(PackageBillingCycleEnum::class)],
            'is_active' => ['sometimes', 'boolean'],

            // Modules array
            'modules' => ['required', 'array', 'distinct'],
            'modules.*.id' => ['required', 'uuid', 'distinct', 'exists:modules,id'],
        ];

        // module features validation
        foreach ($this->input('modules', []) as $i => $module) {
            $moduleId = $module['id'] ?? null;

            if ($moduleId) {
                $rules["modules.$i.features"] = ['nullable', new ValidModuleFeatures($moduleId)];
            }
        }

        return $rules;
    }

    public function createCreatePackageDTO(): CreatePackageDTO
    {
        $modules = collect($this->input('modules', []))
            ->map(fn($mod) => new PackageModuleDTO(
                id: $mod['id'],
                features: collect($mod['features'] ?? [])->map(fn($feat) => new PackageFeatureDTO(
                    id: $feat['id'],
                    is_enabled: (bool) $feat['is_enabled'],
                    limit: $feat['limit'] ?? null,
                    limit_type: isset($feat['limit_type']) ? FeatureLimitTypeEnum::from($feat['limit_type']) : null,
                ))->toArray()
            ))->toArray();

        return new CreatePackageDTO(
            name: $this->input('name'),
            price: (float) $this->input('price'),
            billing_cycle: PackageBillingCycleEnum::from($this->input('billing_cycle')),
            is_active: $this->boolean('is_active', true),
            modules: $modules,
        );
    }
}
