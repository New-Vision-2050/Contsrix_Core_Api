<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Rules;

use Modules\Leave\LeavePolicy\Models\LeavePolicy;
use Ramsey\Uuid\UuidInterface;

class AnnualYearProtectionRules
{
    private const PROTECTED_POLICY_NAME = 'Annual Year';

    public static function isAnnualYearPolicy(UuidInterface $id): bool
    {
        try {
            $policy = LeavePolicy::where('id', $id->toString())
                ->where('company_id', tenant('id'))
                ->first();

            return $policy && $policy->name === self::PROTECTED_POLICY_NAME;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function validateUpdateFields(UuidInterface $id, array $requestData): array
    {
        $errors = [];

        if (self::isAnnualYearPolicy($id)) {
            if (isset($requestData['name']) && $requestData['name'] !== self::PROTECTED_POLICY_NAME) {
                $errors['name'] = 'The name field cannot be modified for Annual Year policy.';
            }

            if (isset($requestData['total_days'])) {
                $errors['total_days'] = 'The total days field cannot be modified for Annual Year policy.';
            }
        }

        return $errors;
    }

    public static function canDelete(UuidInterface $id): bool
    {
        return !self::isAnnualYearPolicy($id);
    }

    public static function getProtectedPolicyName(): string
    {
        return self::PROTECTED_POLICY_NAME;
    }
}
