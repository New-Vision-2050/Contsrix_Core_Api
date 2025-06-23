<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IdentityValidationRule implements Rule
{
    private ?string $email;

    public function __construct(?string $email = null)
    {
        $this->email = $email;
    }

    public function passes($attribute, $value): bool
    {
        if (!$value) {
            return true; // If identity is null or empty, pass validation
        }

        // Check if the email exists in company_users
        $userEmail = $this->email ? DB::table('company_users')->where('email', $this->email)->first() : null;
        
        // Check if the identity number already exists in company_users table
        $userIdentity = DB::table('company_users')->where('identity', $value)->first();
        
        // If email is not found, identity should also not exist
        if (!$userEmail && $userIdentity) {
            // If we're updating an existing user, allow the same identity number
            if (request()->route('id') && $userIdentity->id === request()->route('id')) {
                return true;
            }
            return false;
        }
        
        // If email and identity both exist, they should belong to the same user
        if ($userEmail && $userIdentity) {
            if ($userEmail->global_id != $userIdentity->global_id) {
                return false;
            }
        }

        return true; // Identity number is unique or matches the email's user
    }

    public function message(): string
    {
        return __("validation.identity_validation_error");
    }
}
