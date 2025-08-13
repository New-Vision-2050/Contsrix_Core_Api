<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Observers;

use Modules\Company\CompanyCore\Models\Company;
use Modules\JobTitle\Models\JobTitle;
use Ramsey\Uuid\Uuid;
use Modules\Shared\JobType\DTO\CreateJobTypeWithCompanyDTO;
use Modules\Shared\JobType\Services\JobTypeCRUDService;

class CompanyObserver
{
    //public function created(Company $company): void
    //{
    //    $createJobTypeWithCompanyDTO = new CreateJobTypeWithCompanyDTO(
    //        name: 'مجلس ادارة',
    //        companyId: Uuid::fromString($company->id),
    //        status: 1
    //    );
    //
    //    $jobType = app(JobTypeCRUDService::class)->createWithCompany($createJobTypeWithCompanyDTO);
    //
    //    JobTitle::create([
    //        'type' => 'general_manager',
    //        'name' => ['ar' => 'مدير عام', 'en' => 'General Manager'],
    //        'job_type_id' => $jobType->id,
    //        'description' => 'مدير عام',
    //        'status' => 1,
    //        'company_id' => $company->id,
    //    ]);
    //
    //}


}
