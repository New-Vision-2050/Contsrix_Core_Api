<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;
use Modules\Subscription\Package\Enums\PeriodUnitEnum;
use Modules\Subscription\Package\Models\Package;
use Ramsey\Uuid\Uuid;

class MainCompanyAccessProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Create Main Company Access Program
        $companyAccessProgram = CompanyAccessProgram::firstOrCreate([
            'name' => 'Main Access Program',
        ], [
            'programs' => json_encode([['permission_id' => Uuid::uuid4()->toString(), 'limit' => 100]]),
            'is_active' => true,
        ]);

        // 2. Create Main Package
        $package = Package::firstOrCreate([
            'name' => 'Main Package',
            'company_access_program_id' => $companyAccessProgram->id,
        ], [
            'price' => 0.00,
            'currency' => 'USD',
            'subscription_period' => 1,
            'subscription_period_unit' => PeriodUnitEnum::YEAR->value,
            'is_active' => true,
        ]);

        // 3. Assign package to the first company
        $company = Company::first();

        if ($company) {
            $company->packages()->syncWithoutDetaching([
                $package->id => [
                    'subscribed_at' => now(),
                    'expires_at' => now()->addYear(),
                    'is_active' => true,
                ]
            ]);
        }
    }
}
