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
            Setting::firstOrCreate(["key" => "continue_with_otp","company_id"=>tenant("id")??Company::query()->first()->id], ["key" => "continue_with_otp", "value" => 0,"company_id"=>tenant("id")??Company::query()->first()->id]);
        //}
    }
}
