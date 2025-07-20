<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Subscription\Package\Models\Package;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // Get the Main Package
            $mainPackage = Package::where('name', 'Main Package')->first();

            if ($mainPackage) {
                // Get all companies that don't already have the Main Package assigned
                $companiesWithoutMainPackage = Company::whereDoesntHave('packages', function ($query) use ($mainPackage) {
                    $query->where('package_id', $mainPackage->id);
                })->get();

                // Assign Main Package to all companies that don't have it
                foreach ($companiesWithoutMainPackage as $company) {
                    // Check if the company_package record doesn't already exist
                    $existingRecord = DB::table('company_package')
                        ->where('company_id', $company->id)
                        ->where('package_id', $mainPackage->id)
                        ->first();

                    if (!$existingRecord) {
                        DB::table('company_package')->insert([
                            'company_id' => $company->id,
                            'package_id' => $mainPackage->id,
                            'subscribed_at' => now(),
                            'expires_at' => now()->addYear(), // 1 year subscription
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function () {
            // Get the Main Package
            $mainPackage = Package::where('name', 'Main Package')->first();

            if ($mainPackage) {
                // Remove Main Package assignment from all companies
                // Only remove records created by this migration (optional - you may want to keep them)
                DB::table('company_package')
                    ->where('package_id', $mainPackage->id)
                    ->delete();
            }
        });
    }
};
