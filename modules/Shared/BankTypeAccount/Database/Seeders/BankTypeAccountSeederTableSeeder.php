<?php

namespace Modules\Shared\BankTypeAccount\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\BankTypeAccount\Models\BankTypeAccount;
use Ranium\SeedOnce\Traits\SeedOnce;

class BankTypeAccountSeederTableSeeder extends Seeder
{
    use SeedOnce;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $data = [
            ['ar' => 'الرواتب', 'en' => 'Salaries','code'=>'salaries'],
            ['ar' => 'العهد', 'en' => 'Custody','code'=>'custody'],
            ['ar' => 'الافتراضي', 'en' => 'Default','code'=>'default'],
        ];

        foreach ($data as $index => $item) {
            BankTypeAccount::create(
                ['name' => ['en' => $item['en'], 'ar' => $item['ar']], 'code' => $item['code']]
            );
        }
    }
}
