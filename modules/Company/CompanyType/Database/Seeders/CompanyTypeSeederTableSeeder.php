<?php

namespace Modules\Company\CompanyType\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Country\Models\Country;
use Ramsey\Uuid\Uuid;
use Ranium\SeedOnce\Traits\SeedOnce;

class CompanyTypeSeederTableSeeder extends Seeder
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

        $companyTypes = [
            ['name' => 'فردية'],
            ['name' => 'جماعية'],
            ['name' => 'عمل حر']
        ];

        $namespace = Uuid::NAMESPACE_DNS;
        foreach ($companyTypes as $companyType) {
            $name = $companyType['name'];
            CompanyType::query()->insertOrIgnore(
                ['id' => Uuid::uuid5($namespace, $name)->toString(), 'name' => $companyType['name']]
            );
        };
        $countries = Country::active()->get();

        $companyTypes = CompanyType::get();

        foreach ($countries as $country) {
            foreach ($companyTypes as $companyType) {
                $name = $country['name'] . $companyType['name'];
                \DB::table('company_type_countries')->insertOrIgnore(
                    [
                        'id' => Uuid::uuid5($namespace, $name)->toString(),
                        'company_type_id' => $companyType->id,
                        'country_id' => $country->id,
                    ]
                );
            }
        }
    }
}
