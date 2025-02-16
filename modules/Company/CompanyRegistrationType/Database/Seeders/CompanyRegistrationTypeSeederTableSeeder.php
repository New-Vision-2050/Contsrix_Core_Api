<?php

namespace Modules\Company\CompanyRegistrationType\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;

class CompanyRegistrationTypeSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $data = [
            ['en' => 'Commercial Register', 'ar' => 'سجل تجاري', 'type' => 1],
            ['en' => 'Classification', 'ar' => 'تصنيف', 'type' => 2],
            ['en' => 'Without Commercial Register', 'ar' => 'بدون سجل تجاري', 'type' => 3],
        ];

        foreach ($data as $item) {
            CompanyRegistrationType::firstOrCreate(
                ['type' => $item['type']],
                ['name' => ['en' => $item['en'], 'ar' => $item['ar']]]
            );
        }

    }
}
