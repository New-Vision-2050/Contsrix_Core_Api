<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\User\Models\User;

class CanDeleteCompanyUserRule implements Rule
{
    private string $message = '';
    
    public function __construct()
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // Get the company user being deleted
        $companyUserToDelete = CompanyUser::find($value);
        if (!$companyUserToDelete) {
            $this->message = __('validation.exists', ['attribute' => 'company user']);
            return false;
        }

        // Check if trying to delete admin account
        if ($companyUserToDelete->email === 'admin@constrix-nv.com') {
            $this->message = __('validation.admin_account_cannot_be_deleted');
            return false;
        }

        // Check if trying to delete self
        $currentUserId = auth()->user()->global_company_user_id ?? null;
        if ($currentUserId && $currentUserId === $companyUserToDelete->global_id) {
            $this->message = __('validation.cannot_delete_yourself');
            return false;
        }

        // Check if trying to delete company owner
        $isOwner = User::where('global_company_user_id', $companyUserToDelete->global_id)
            ->where('is_owner', true)
            ->exists();
            
        if ($isOwner) {
            $this->message = __('validation.cannot_delete_company_owner');
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }
}
