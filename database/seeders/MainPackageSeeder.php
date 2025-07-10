<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Models\Company;
use Modules\RoleAndPermission\Models\Permission;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;
use Modules\Subscription\Enums\PeriodUnitEnum;
use Modules\Subscription\Package\Models\Package;

class MainPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Create Main Company Access Program
            $accessProgram = CompanyAccessProgram::firstOrCreate([
                'name' => 'Main Access Program',
            ], [
                'is_active' => true,
            ]);

            // 2. Create Main Package
            $package = Package::firstOrCreate([
                'name' => 'Main Package',
                'company_access_program_id' => $accessProgram->id,
            ], [
                'price' => 0.00,
                'currency' => 'USD',
                'subscription_period' => 1,
                'subscription_period_unit' => PeriodUnitEnum::Year->value,
                'trial_period' => 0,
                'trial_period_unit' => PeriodUnitEnum::Day->value,
                'is_active' => true,
            ]);

            // 3. Sync all permissions to the package
            $permissions = Permission::all()->pluck('id');
            $package->permissions()->sync($permissions);

            // 4. Assign the package to the first company
            $company =tenant("id")? Company::find(tenant("id")): Company::first();
            if ($company) {
                $company->packages()->syncWithoutDetaching([
                    $package->id => [
                        'subscribed_at' => now(),
                        'expires_at' => now()->addYear(),
                        'is_active' => true,
                    ]
                ]);
            }
        });
    }
}
