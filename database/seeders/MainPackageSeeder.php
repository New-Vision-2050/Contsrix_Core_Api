<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Company\CompanyCore\Models\Company;
use Modules\RoleAndPermission\Models\Permission;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;
use Modules\Subscription\Enums\PeriodUnitEnum;
use Modules\Subscription\Package\Models\Package;
use Modules\Country\Models\Country;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Company\CompanyField\Models\CompanyField;
use Ramsey\Uuid\Uuid;

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
            // 1. Create Main Company Access Program without triggering observers
            $accessProgram = CompanyAccessProgram::withoutEvents(function () {
                return CompanyAccessProgram::firstOrCreate([
                    'name' => 'Main Access Program',
                ], [
                    'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(), // Explicitly generate UUID
                    'is_active' => true,
                    'is_main_program' => true,
                ]);
            });

            // 1.1. Always sync all countries, company types, and company fields to Main Access Program
            // This ensures newly added data is included when the seeder runs again

            // Assign all countries
            $countries = Country::all()->pluck('id');
            $accessProgram->countries()->sync($countries);

            // Assign all company types
            $companyTypes = CompanyType::all()->pluck('id');
            $accessProgram->companyTypes()->sync($companyTypes);

            // Assign all company fields
            $companyFields = CompanyField::all()->pluck('id');
            $accessProgram->companyFields()->sync($companyFields);

            // 1.2. Assign all programs and sub-entities not in excluded patterns
            $excludedPermissionPatterns = [
                'companies',
                'users',
                'subscription',
                "program-management",
                "permissions"
            ];

            // Get all permissions and extract programs/sub-entities from permission names
            $allPermissions = Permission::where('status', true)->get();
            $programsData = [];
            $subEntitiesData = [];
            $processedPrograms = [];
            $processedSubEntities = [];

            foreach ($allPermissions as $permission) {
                // Parse permission name pattern: {program}.{sub_entity}.{action}
                $nameParts = explode('.', $permission->name);

                if (count($nameParts) >= 3) {
                    $program = $nameParts[0];
                    $subEntity = $nameParts[1];
                    if (str_contains($subEntity, "*")) {
                        $resources = explode('*', $nameParts[1]);
                        $subEntity = $resources[0];
                        if (uuid_is_valid($resources[1])) {
                            $subEntity = $nameParts[1];
                        }

                    }

                    // Check if program is not in excluded patterns
                    $isExcluded = false;
                    foreach ($excludedPermissionPatterns as $pattern) {
                        if (str_contains($program, $pattern)) {
                            $isExcluded = true;
                            break;
                        }
                    }

                    if (!$isExcluded) {
                        // Add program if not already processed
                        if (!in_array($program, $processedPrograms)) {
                            $programsData[] = [
                                'company_access_program_id' => $accessProgram->id,
                                'program_id' => $program,
                            ];
                            $processedPrograms[] = $program;
                        }

                        // Add sub-entity if not already processed
                        $subEntityKey = $subEntity;
                        if (!in_array($subEntityKey, $processedSubEntities)) {
                            $subEntitiesData[] = [
                                'company_access_program_id' => $accessProgram->id,
                                'sub_entity_id' => $subEntity,
                            ];
                            $processedSubEntities[] = $subEntityKey;
                        }
                    }
                }
            }

            // Assign programs to Main Access Program
            if (!empty($programsData)) {
                $accessProgram->programs()->delete(); // Clear existing
                $accessProgram->programs()->insert($programsData);
            }

            // Assign sub-entities to Main Access Program
            if (!empty($subEntitiesData)) {
                $accessProgram->subEntities()->delete(); // Clear existing
                $accessProgram->subEntities()->insert($subEntitiesData);
            }

            Log::info("MainPackageSeeder: Assigned " . count($programsData) . " programs and " . count($subEntitiesData) . " sub-entities to Main Access Program (extracted from permissions)");

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
                'is_main_package' =>1
            ]);



            $totalPermissions = Permission::count();

            $permissions = Permission::where(function($query) use ($excludedPermissionPatterns) {
                foreach ($excludedPermissionPatterns as $pattern) {
                    $query->where('name', 'NOT LIKE', "{$pattern}.".".%");
                }
            })->pluck('id');

            $excludedCount = $totalPermissions - $permissions->count();

            Log::info("MainPackageSeeder: Excluded {$excludedCount} permissions, assigning {$permissions->count()} permissions to Main Package");

            $package->permissions()->sync($permissions);

            $package->companyTypes()->sync($companyTypes);


            $package->companyFields()->sync($companyFields);

            // 4. Assign the package to the first company
//            $company =tenant("id")? Company::find(tenant("id")): Company::first();
//            if ($company) {
//                $company->packages()->syncWithoutDetaching([
//                    $package->id => [
//                        'subscribed_at' => now(),
//                        'expires_at' => now()->addYear(),
//                        'is_active' => true,
//                    ]
//                ]);
//            }
        });
    }
}
