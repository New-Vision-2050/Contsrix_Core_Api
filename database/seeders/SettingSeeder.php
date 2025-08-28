<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Setting\Models\Setting;
use Ranium\SeedOnce\Traits\SeedOnce;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        //if (App::environment('production') == false) {
            Setting::firstOrCreate(["key" => "continue_with_otp","company_id"=>tenant("id")??"560005d6-04b8-53b3-9889-d312648288e3"], ["key" => "continue_with_otp", "value" => 0,"company_id"=>tenant("id")??"560005d6-04b8-53b3-9889-d312648288e3"]);
            Setting::firstOrCreate(["key" => "is_share_client","company_id"=>tenant("id")??"560005d6-04b8-53b3-9889-d312648288e3"], ["key" => "is_share_client", "value" => 1,"company_id"=>tenant("id")??"560005d6-04b8-53b3-9889-d312648288e3"]);
            Setting::firstOrCreate(["key" => "is_share_broker","company_id"=>tenant("id")??"560005d6-04b8-53b3-9889-d312648288e3"], ["key" => "is_share_broker", "value" => 1,"company_id"=>tenant("id")??"560005d6-04b8-53b3-9889-d312648288e3"]);


        //}
    }
}
