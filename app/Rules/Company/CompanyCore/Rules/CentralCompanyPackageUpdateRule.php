<?php

declare(strict_types=1);

namespace App\Rules\Company\CompanyCore\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;

class CentralCompanyPackageUpdateRule implements ValidationRule
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Get the current company using the trait
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();

        // Get current packages assigned to the company
        $currentPackages = $company->packages()->pluck('id')->toArray();
        $newPackages = $value ?? []; // Handle null packages (for central companies)

        // Compare arrays to check if packages are being modified
        sort($currentPackages);
        sort($newPackages);

        if ($currentPackages !== $newPackages) {
            // Check if this is a central company for specific error message
            if ($company->is_central_company) {
                $fail(__('validation.central_company_cannot_update_packages'));
            } elseif (auth()->user()->company_id == $company->id) {
                // Regular company trying to update their own packages
                $fail(__('validation.company_cannot_update_own_packages'));
            }
            // If it's not a central company AND not the user's own company, allow the update
        }
    }
}
