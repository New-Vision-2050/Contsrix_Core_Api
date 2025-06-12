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
        $userEmail = DB::table('company_users')->where('email', $this->email)->first();
        $userPhones = DB::table('users')->where('phone', $phone)->get();

        // If email is not found, phone should also not exist
        if (!$userEmail && count($userPhones)) {
            return false;
        }
        //if email exist and phone exist should be for same user with same global_company_user_id
        if($userEmail && count($userPhones) > 0) {
            foreach ($userPhones as $user){
                if($user->global_company_user_id != $userEmail->global_id )
                {
                    return false;
                }
            }
        }

        return true;
    }

    public function message(): string
    {
        return __("validation.phone_email_consistency-error");
    }
}
