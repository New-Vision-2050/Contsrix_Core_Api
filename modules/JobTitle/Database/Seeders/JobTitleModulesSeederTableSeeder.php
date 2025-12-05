<?php

namespace Modules\JobTitle\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Company\CompanyCore\Models\Company;
use Modules\JobTitle\Models\JobTitle;
use Modules\Shared\JobType\Models\JobType;
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

        $targetCompanyId = tenant("id") ?? $companyId;

        $data = [

            ['en' => 'General Manager', 'ar' => 'مدير عام','type'=>'general_manager']
            //['en' => 'Head of Department', 'ar' => 'رئيس قسم','type'=>'head_department'],
            //['en' => 'hr manager', 'ar' => 'مدير الموارد البشرية','type'=>'hr_manager'],
        ];

        $jobType = JobType::where('company_id', $targetCompanyId)
            ->whereHas('translations',function($q) use ($data){
                $q->where('content','like','مجلس ادارة');
            })
            ->first();


        if (!$jobType) {
            $createJobTypeWithCompanyDTO = new CreateJobTypeWithCompanyDTO(
                name: 'مجلس ادارة',
                companyId: Uuid::fromString($targetCompanyId),
                status: 1
            );
            $jobType = app(JobTypeCRUDService::class)->createWithCompany($createJobTypeWithCompanyDTO);
        }

        foreach ($data as $item) {
            JobTitle::firstOrCreate(
                ['type' => $item['type'], "company_id" => $targetCompanyId],
                [
                    'name' => ['en' => $item['en'], 'ar' => $item['ar']],
                    'type' => $item['type'],
                    "company_id" => $targetCompanyId,
                    'description' => 'مدير عام',
                    'job_type_id' => $jobType->id,
                    'status' => 1
                ]
            );
        }

    }
}
