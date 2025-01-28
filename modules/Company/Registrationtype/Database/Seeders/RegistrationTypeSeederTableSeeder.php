<?php

namespace Modules\Company\RegistrationType\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Company\RegistrationType\Models\RegistrationType;
use Modules\Country\Models\Country;

class RegistrationTypeSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        RegistrationType::create([
            'name'=>'سجل تجاري',
        ]);


        // $this->call("OthersTableSeeder");
    }
}
