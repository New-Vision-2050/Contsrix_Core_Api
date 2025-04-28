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
use Modules\Shared\AcademicSpecialization\Database\Seeders\AcademicSpecializationsSeederTableSeeder;
use Modules\Shared\University\Database\Seeders\UniversitiesSeederTableSeeder;
use Modules\Shared\Bank\Database\Seeders\BanksModulesSeederTableSeeder;
use Modules\Shared\JobType\Database\Seeders\JobTypeSeederTable;
use Modules\Shared\NatureWork\Database\Seeders\NatureWorkSeederTableSeeder;
use Modules\Shared\Period\Database\Seeders\PeriodSeederTableSeeder;
use Modules\Shared\Privilege\Database\Seeders\PrivilegeModulesSeederTableSeeder;
use Modules\Shared\ProfessionalBodie\Database\Seeders\ProfessionalBodiesSeeder;
use Modules\Shared\RightTerminate\Database\Seeders\RightTerminateSeederTableSeeder;
use Modules\Shared\SalaryType\Database\Seeders\SalaryTypeSeederTableSeeder;
use Modules\Shared\TimeUnit\Database\Seeders\TimeUnitSeederTableSeeder;
use Modules\Shared\TimeZone\Database\Seeders\TimeZoneCountrySeederTableSeeder;
use Modules\Shared\TimeZone\Database\Seeders\TimeZoneSeederTableSeeder;
use Modules\Shared\TypeAllowance\Database\Seeders\TypeAllowancesSeederTableSeeder;
use Modules\Shared\TypePrivilege\Database\Seeders\TypePrivilegeSeederTableSeeder;
use Modules\Shared\TypeWorkingHour\Database\Seeders\TypeWorkingHourSeederTableSeeder;
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
//        $this->call(UniversitiesTableSeeder::class);
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

        $this->call(BanksModulesSeederTableSeeder::class);

        $this->call(AcademicQualificationSeederTableSeeder::class);

        $this->call(AcademicSpecializationsSeederTableSeeder::class);

        $this->call(UniversitiesSeederTableSeeder::class);
        $this->call(ProfessionalBodiesSeeder::class);
        $this->call(PrivilegeModulesSeederTableSeeder::class);

        $this->call(PeriodSeederTableSeeder::class);
        $this->call(TypeAllowancesSeederTableSeeder::class);
        $this->call(TypePrivilegeSeederTableSeeder::class);

        $this->call(TimeZoneCountrySeederTableSeeder::class);

        $this->call(JobTypeSeederTable::class);

        $this->call(SalaryTypeSeederTableSeeder::class);
        $this->call(TimeUnitSeederTableSeeder::class);

        $this->call(NatureWorkSeederTableSeeder::class);
        $this->call(RightTerminateSeederTableSeeder::class);
        $this->call(TypeWorkingHourSeederTableSeeder::class);
    }
}
