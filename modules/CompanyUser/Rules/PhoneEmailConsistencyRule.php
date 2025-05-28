<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class PhoneEmailConsistencyRule implements Rule
{
    private string $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function passes($attribute, $value): bool
    {
        $phone = str_replace(" ", "", $value);
        $emailExists = DB::table('company_users')->where('email', $this->email)->exists();
        $phoneExists = DB::table('users')->where('phone', $phone)->exists();

        // If email is not found, phone should also not exist
        if (!$emailExists && $phoneExists) {
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return __("validation.phone_email_consistency-error");
    }
}
