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

        CompanyRegistrationType::firstOrCreate([
            'name'=>'سجل تجاري',
            'type' => 1
        ]);

        CompanyRegistrationType::firstOrCreate([
            'name'=>'تصنيف',
            'type' => 2
        ]);

        CompanyRegistrationType::firstOrCreate([
            'name'=>'بدون سجل تجاري',
            'type' => 3
        ]);

    }
}
