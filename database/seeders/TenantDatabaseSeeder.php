<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\CompanyPackageAssignmentSeeder;
use Modules\DocumentType\Database\Seeders\DocumentTypeSeederTableSeeder;
use Modules\Setting\Database\Seeders\DriverTableSeeder;
use Modules\Setting\Database\Seeders\QuestionSettingTableSeeder;
use Modules\Setting\Database\Seeders\DefaultLoginWaySeederTableSeeder;
use Modules\JobTitle\Database\Seeders\JobTitleModulesSeederTableSeeder;
use Modules\Setting\Database\Seeders\DefaultIdentifierSeederTableSeeder;
use Modules\User\Database\Seeders\GenaralAdminSeedTableSeeder;
use Modules\Leave\LeavePolicy\Database\Seeders\LeavePolicySeeder;
use Modules\Leave\LeaveType\Database\Seeders\LeaveTypeBranchSeeder;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
//        $this->call(CountrySeederTableSeeder::class);
//        $this->call(TimeZoneSeederTableSeeder::class);
//        $this->call(LanguageSeederTableSeeder::class);
//        $this->call(CurrencySeederTableSeeder::class);
//        $this->call(AdminSeedTableSeeder::class);

//        $this->call(CompanyModulesSeederTableSeeder::class);
        $this->call(GenaralAdminSeedTableSeeder::class);

        $this->call(CompanyPackageAssignmentSeeder::class);

        $this->call(JobTitleModulesSeederTableSeeder::class);
        $this->call(SettingSeeder::class);
        $this->call(DriverTableSeeder::class);
        $this->call(QuestionSettingTableSeeder::class);
        $this->call(DefaultIdentifierSeederTableSeeder::class);

        $this->call(DefaultLoginWaySeederTableSeeder::class);

        // Create default Annual Year leave policy for new companies
        $this->call(LeavePolicySeeder::class);
        $this->call(DocumentTypeSeederTableSeeder::class);

//        $this->call(MainPackageSeeder::class);
    }
}
