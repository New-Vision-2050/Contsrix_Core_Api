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

        CompanyRegistrationType::create([
            'name'=>'سجل تجاري',
        ]);


        // $this->call("OthersTableSeeder");
    }
}
