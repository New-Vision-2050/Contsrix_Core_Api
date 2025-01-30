<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Modules\Setting\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        if (App::environment('production') == false) {
            Setting::firstOrCreate(["key" => "continue_with_otp"], ["key" => "continue_with_otp", "value" => 0]);
        }
    }
}
