<?php

namespace Modules\Ecommerce\Warehous\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyField\Models\CompanyField;
use Modules\Ecommerce\Warehous\Models\Warehous;
use Ranium\SeedOnce\Traits\SeedOnce;
use Ramsey\Uuid\Uuid;

class WarehousSeederTableSeeder extends Seeder
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
        $warehouses = [
            [
                'name' => 'مخزن جدة',
            ],
            [
                'name' => 'مخزن الرياض',
            ],
        ];
        foreach ($warehouses as $warehouse) {

            Warehous::create([
                'company_id' => Company::where('is_central_company',1)->first()->id,
                'name' => $warehouse['name'],
            ]);
        }
    }
}
