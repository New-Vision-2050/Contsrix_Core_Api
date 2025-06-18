<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ResidenceValidationRule implements Rule
{
    private ?string $email;

    public function __construct(?string $email = null)
    {
        $this->email = $email;
    }

    public function passes($attribute, $value): bool
    {
        if (!$value) {
            return true; // If residence is null or empty, pass validation
        }

        // Check if the email exists in company_users
        $userEmail = $this->email ? DB::table('company_users')->where('email', $this->email)->first() : null;
        
        // Check if the residence number already exists in company_users table
        $userResidence = DB::table('company_users')->where('residence', $value)->first();
        
        // If email is not found, residence should also not exist
        if (!$userEmail && $userResidence) {
            // If we're updating an existing user, allow the same residence number
            if (request()->route('id') && $userResidence->id === request()->route('id')) {
                return true;
            }
            return false;
        }
        
        // If email and residence both exist, they should belong to the same user
        if ($userEmail && $userResidence) {
            if ($userEmail->global_id != $userResidence->global_id) {
                return false;
            }
        }

        return true; // Residence number is unique or matches the email's user
    }

    public function message(): string
    {
        return __("validation.residence_validation_error");
    }
}
