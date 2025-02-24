<?php

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Setting\Models\IdentifierSetting;
use Modules\Setting\Models\LoginWay;
use Modules\Setting\Models\Setting;

class DefaultIdentifierSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        if (App::environment('production') == false) {
            $loginWay = IdentifierSetting::firstOrCreate(
                ["name" => "email"],
                [
                    "name" => "email",
                    "default" => 1
                ]
            );

        }

    }
}
