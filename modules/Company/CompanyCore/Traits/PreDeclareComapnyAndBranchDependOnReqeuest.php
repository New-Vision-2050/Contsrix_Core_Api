<?php

namespace Modules\Company\CompanyCore\Traits;

use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;

trait PreDeclareComapnyAndBranchDependOnReqeuest
{
    /**
     * @return array
     * @throws \Exception
     * @description Declare company and branch using request
     */
    public function declareCompanyAndBranchUsingRequest()
    {
        /**
         * if we not in central company would no data except company that we logged in
         * in central company we would see all companies
         */
        $companyId = tenant("id");
        $company = Company::query()->where("id", tenant("id"))->first();

        $branchId = $company->firstBranch->id;
        $branch = $company->firstBranch;
        if ($this->has("company_id")) {
            $company = Company::query()->where("id", $this->company_id)->first();
            if ($company == null) {
                throw new \Exception(__("validation.company-not-found"), 404);
            }

            $companyId = $this->company_id;
            $branchId = $company->firstBranch->id;

        }
        if ($this->has("branch_id")) {
            $branch = ManagementHierarchy::query()->where("id", $this->branch_id)->where("type", "branch")->first();
            if ($branch == null) {
                throw new \Exception(__("validation.branch-not-found"), 404);
            }
            if ($this->has("company_id") && $this->company_id != $branch->company_id) {
                throw new \Exception(__("validation.integrity-error"), 404);
            }
            $companyId = $branch->company_id;
            $branchId = $this->branch_id;

        }

        return [$company, $branch];
    }
}
