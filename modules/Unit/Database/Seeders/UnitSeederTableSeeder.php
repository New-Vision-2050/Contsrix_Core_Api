<?php

namespace Modules\Unit\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Setting\Models\IdentifierSetting;
use Modules\Setting\Models\LoginWay;
use Modules\Setting\Models\Setting;
use Ramsey\Uuid\Uuid;
use Ranium\SeedOnce\Traits\SeedOnce;
use Modules\Unit\Models\Unit;

class UnitSeederTableSeeder extends Seeder
{
    use SeedOnce;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $names = ["kg","g","ml","l"];
        foreach ($names as $name) {
            Unit::create([
                "name" => $name,
            ]);            
        }
        
    }
}
