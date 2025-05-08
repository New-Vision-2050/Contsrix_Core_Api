<?php

namespace Modules\Shared\JobType\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Shared\JobType\Models\JobType;
use Ranium\SeedOnce\Traits\SeedOnce;

class JobTypeSeederTable extends Seeder
{
    use SeedOnce;

    public function run()
    {
        Model::unguard();

        $company = Company::where('user_name', 'new-vision')->first();

        $jobTypes = [
            ['ar' => 'إداري',        'en' => 'Administrator',  'type' => 'administrator',    'company_id' => $company->id],
            ['ar' => 'مبرمج',       'en' => 'Programmer',     'type' => 'programmer',       'company_id' => $company->id],
            ['ar' => 'مصمم',        'en' => 'Designer',       'type' => 'designer',         'company_id' => $company->id],
            ['ar' => 'مدير مشروع',  'en' => 'Project Manager','type' => 'project_manager',  'company_id' => $company->id],
            ['ar' => 'محاسب',       'en' => 'Accountant',     'type' => 'accountant',       'company_id' => $company->id],
            ['ar' => 'مسوق',        'en' => 'Marketer',       'type' => 'marketer',         'company_id' => $company->id],
        ];

        foreach ($jobTypes as $jobType) {
            JobType::firstOrCreate(
                ['type' => $jobType['type']],
                [
                    'name'       => ['en' => $jobType['en'], 'ar' => $jobType['ar']],
                    'type'       => $jobType['type'],
                    'company_id' => $jobType['company_id'],
                ]
            );
        }
    }
}
