<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Company\CompanyCore\Database\Seeders\CompanyModulesSeederTableSeeder;
use Modules\Country\Database\Seeders\CountrySeederTableSeeder;
use Modules\Shared\Language\Database\Seeders\LanguagesTableSeeder;
use Modules\Shared\University\Database\Seeders\UniversitiesTableSeeder;
use Modules\JobTitle\Database\Seeders\JobTitleModulesSeederTableSeeder;
use Modules\RoleAndPermission\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Setting\Database\Seeders\DefaultIdentifierSeederTableSeeder;
use Modules\Setting\Database\Seeders\DefaultLoginWaySeederTableSeeder;
use Modules\Setting\Database\Seeders\DriverTableSeeder;
use Modules\Setting\Database\Seeders\QuestionSettingTableSeeder;
use Modules\Shared\Currency\Database\Seeders\CurrencySeederTable;
use Modules\User\Database\Seeders\AdminSeedTableSeeder;
use Modules\Shared\AcademicQualification\Database\Seeders\AcademicQualificationSeederTableSeeder;
use Modules\Shared\AcademicSpecialization\Database\Seeders\AcademicSpecializationSeederTableSeeder;
use Modules\Shared\University\Database\Seeders\UniversitySeederTableSeeder;
use Modules\Shared\Bank\Database\Seeders\BankModulesSeederTableSeeder;
use Modules\Shared\Period\Database\Seeders\PeriodSeederTableSeeder;
use Modules\Shared\Privilege\Database\Seeders\PrivilegeModulesSeederTableSeeder;
use Modules\Shared\ProfessionalBodie\Database\Seeders\ProfessionalBodieSeeder;
use Modules\Shared\TimeZone\Database\Seeders\TimeZoneSeederTableSeeder;
use Modules\Shared\TypeAllowance\Database\Seeders\TypeAllowanceSeederTableSeeder;
use Modules\Shared\TypePrivilege\Database\Seeders\TypePrivilegeSeederTableSeeder;
use Ranium\SeedOnce\Traits\SeedOnce;
class DatabaseSeeder extends Seeder
{
    use SeedOnce;
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CurrencySeederTable::class);
        $this->call(UniversitiesTableSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(JobTitleModulesSeederTableSeeder::class);
        $this->call(TimeZoneSeederTableSeeder::class);
        $this->call(LanguagesTableSeeder::class);
        $this->call(AdminSeedTableSeeder::class);

        $this->call(CompanyModulesSeederTableSeeder::class);

        $this->call(SettingSeeder::class);
        $this->call(DriverTableSeeder::class);
        $this->call(QuestionSettingTableSeeder::class);
        $this->call(DefaultIdentifierSeederTableSeeder::class);

        $this->call(DefaultLoginWaySeederTableSeeder::class);

        $this->call(BankModulesSeederTableSeeder::class);

        $this->call(AcademicQualificationSeederTableSeeder::class);

        $this->call(AcademicSpecializationSeederTableSeeder::class);

//        $this->call(UniversitySeederTableSeeder::class);
        $this->call(ProfessionalBodieSeeder::class);
        $this->call(PrivilegeModulesSeederTableSeeder::class);

        $this->call(PeriodSeederTableSeeder::class);
        $this->call(TypeAllowanceSeederTableSeeder::class);
        $this->call(TypePrivilegeSeederTableSeeder::class);




    }
}
