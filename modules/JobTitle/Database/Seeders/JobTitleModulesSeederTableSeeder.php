<?php

namespace Modules\JobTitle\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Company\CompanyCore\Models\Company;
use Modules\JobTitle\Models\JobTitle;
use Modules\Shared\JobType\DTO\CreateJobTypeWithCompanyDTO;
use Modules\Shared\JobType\Services\JobTypeCRUDService;
use Ranium\SeedOnce\Traits\SeedOnce;
use Ramsey\Uuid\Uuid;

class JobTitleModulesSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $namespace = Uuid::NAMESPACE_DNS;
        $companyId = Uuid::uuid5($namespace, "new-vision")->toString();

        $data = [

            ['en' => 'General Manager', 'ar' => 'مدير عام','type'=>'general_manager']
            //['en' => 'Head of Department', 'ar' => 'رئيس قسم','type'=>'head_department'],
            //['en' => 'hr manager', 'ar' => 'مدير الموارد البشرية','type'=>'hr_manager'],
        ];

        $createJobTypeWithCompanyDTO = new CreateJobTypeWithCompanyDTO(
            name: 'مجلس ادارة',
            companyId: Uuid::fromString(tenant("id") ?? $companyId),
            status: 1
        );

        $jobType = app(JobTypeCRUDService::class)->createWithCompany($createJobTypeWithCompanyDTO);


        foreach ($data as $item) {
            JobTitle::firstOrCreate(
                ['type' => $item['type'], "company_id" => tenant("id") ?? $companyId],
                [
                    'name' => ['en' => $item['en'], 'ar' => $item['ar']],
                    'type' => $item['type'],
                    "company_id" => tenant("id") ?? $companyId,
                    'description' => 'مدير عام',
                    'job_type_id' => $jobType->id,
                    'status' => 1
                ]
            );
        }

    }
}
