<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class PassportValidationRule implements Rule
{
    private ?string $email;

    public function __construct(?string $email = null)
    {
        $this->email = $email;
    }

    public function passes($attribute, $value): bool
    {
        if (!$value) {
            return true; // If passport is null or empty, pass validation
        }

        // Check if the email exists in company_users
        $userEmail = $this->email ? DB::table('company_users')->where('email', $this->email)->first() : null;
        
        // Check if the passport number already exists in company_users table
        $userPassport = DB::table('company_users')->where('passport', $value)->first();
        
        // If email is not found, passport should also not exist
        if (!$userEmail && $userPassport) {
            // If we're updating an existing user, allow the same passport number
            if (request()->route('id') && $userPassport->id === request()->route('id')) {
                return true;
            }
            return false;
        }
        
        // If email and passport both exist, they should belong to the same user
        if ($userEmail && $userPassport) {
            if ($userEmail->global_id != $userPassport->global_id) {
                return false;
            }
        }

        return true; // Passport number is unique or matches the email's user
    }

    public function message(): string
    {
        return __("validation.passport_validation_error");
    }
}
