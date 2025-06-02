<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class BorderNumberValidationRule implements Rule
{
    private ?string $email;

    public function __construct(?string $email = null)
    {
        $this->email = $email;
    }

    public function passes($attribute, $value): bool
    {
        if (!$value) {
            return true; // If border_number is null or empty, pass validation
        }

        // Check if the email exists in company_users
        $userEmail = $this->email ? DB::table('company_users')->where('email', $this->email)->first() : null;
        
        // Check if the border_number already exists in company_users table
        $userBorderNumber = DB::table('company_users')->where('border_number', $value)->first();
        
        // If email is not found, border_number should also not exist
        if (!$userEmail && $userBorderNumber) {
            // If we're updating an existing user, allow the same border_number
            if (request()->route('id') && $userBorderNumber->id === request()->route('id')) {
                return true;
            }
            return false;
        }
        
        // If email and border_number both exist, they should belong to the same user
        if ($userEmail && $userBorderNumber) {
            if ($userEmail->global_id != $userBorderNumber->global_id) {
                return false;
            }
        }

        return true; // Border number is unique or matches the email's user
    }

    public function message(): string
    {
        return __("validation.border_number_validation_error");
    }
}
