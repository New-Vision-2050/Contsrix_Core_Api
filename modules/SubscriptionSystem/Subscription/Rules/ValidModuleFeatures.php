<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\Rules;


use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\SubscriptionSystem\Subscription\Enums\FeatureLimitTypeEnum;

class ValidModuleFeatures implements ValidationRule
{
    protected string $moduleId;

    public function __construct(string $moduleId)
    {
        $this->moduleId = $moduleId;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail("Features must be an array.");
            return;
        }

        foreach ($value as $index => $feature) {
            $featurePath = "$attribute.$index";

            // Ensure feature has required keys
            if (!isset($feature['id'], $feature['is_enabled'])) {
                $fail("{$featurePath}.id and is_enabled are required.");
                continue;
            }

            $featureId = $feature['id'];

            // Feature must exist and belong to module
            $featureExists = DB::table('features')
                ->where('id', $featureId)
                ->where('module_id', $this->moduleId)
                ->exists();

            if (!$featureExists) {
                $fail("Feature $featureId does not belong to module {$this->moduleId}.");
            }

            // is_enabled must be boolean
            if (!is_bool($feature['is_enabled'])) {
                $fail("{$featurePath}.is_enabled must be true or false.");
            }

            // Validate optional limit
            if (isset($feature['limit']) && (!is_int($feature['limit']) || $feature['limit'] < 0)) {
                $fail("{$featurePath}.limit must be a positive integer.");
            }

            // Validate optional limit_type
            if (isset($feature['limit_type']) && !in_array($feature['limit_type'], FeatureLimitTypeEnum::values(), true)) {
                $fail("{$featurePath}.limit_type is invalid.");
            }
        }
    }
}
