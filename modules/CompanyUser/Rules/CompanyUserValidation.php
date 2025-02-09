<?php

namespace Modules\CompanyUser\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;

class CompanyUserValidation implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    private $companyRepository;
    private $companyId;
    private $countryId;

    public function __construct($companyId, $countryId)
    {
        $this->companyRepository = app(CompanyRepository::class);
        $this->companyId = $companyId;
        $this->countryId = $countryId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $company = $this->companyRepository->findOneBy(["id" => $this->companyId]);
        if ($company === null) {
            return false;
        }

        if ($this->countryId == $company->country_id) {
            return request()->identity != null || request()->passport != null;
        } else {
            return request()->residence != null || request()->border_number != null || request()->passport != null;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        $company = $this->companyRepository->findOneBy(["id" => $this->companyId]);
        if ($company === null) {
            return __("validation.company-not-found");
        }

        if ($this->countryId == $company->country_id) {
            return __("validation.identity-or-passport-required");
        } else {
            return __("validation.passport-or-residence-or-border_number-required");
        }

    }
}
