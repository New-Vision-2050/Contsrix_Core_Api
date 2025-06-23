<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Ranium\SeedOnce\Traits\SeedOnce;
use Modules\Setting\Database\Seeders\DriverTableSeeder;
use Modules\User\Database\Seeders\AdminSeedTableSeeder;
use Modules\Program\Database\Seeders\ProgramDatabaseSeeder;
use Modules\Country\Database\Seeders\CountrySeederTableSeeder;
use Modules\Shared\JobType\Database\Seeders\JobTypeSeederTable;
use Modules\SubEntity\Database\Seeders\RegistrationFormsSeeder;
use Modules\SubEntity\Database\Seeders\SubEntityDatabaseSeeder;
use Modules\Subscription\Database\Seeders\ModuleDatabaseSeeder;
use Modules\Setting\Database\Seeders\QuestionSettingTableSeeder;
use Modules\Subscription\Database\Seeders\FeatureDatabaseSeeder;
use Modules\Shared\Currency\Database\Seeders\CurrencySeederTable;
use Modules\Shared\Language\Database\Seeders\LanguagesTableSeeder;
use Modules\Shared\Period\Database\Seeders\PeriodSeederTableSeeder;
use Modules\Setting\Database\Seeders\DefaultLoginWaySeederTableSeeder;
use Modules\JobTitle\Database\Seeders\JobTitleModulesSeederTableSeeder;
use Modules\Shared\Bank\Database\Seeders\BanksModulesSeederTableSeeder;
use Modules\Shared\TimeZone\Database\Seeders\TimeZoneSeederTableSeeder;
use Modules\Shared\University\Database\Seeders\UniversitiesTableSeeder;
use Modules\Setting\Database\Seeders\DefaultIdentifierSeederTableSeeder;
use Modules\Shared\TimeUnit\Database\Seeders\TimeUnitsSeederTableSeeder;
use Modules\RoleAndPermission\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Shared\NatureWork\Database\Seeders\NatureWorkSeederTableSeeder;
use Modules\Shared\SalaryType\Database\Seeders\SalaryTypeSeederTableSeeder;
use Modules\Shared\University\Database\Seeders\UniversitiesSeederTableSeeder;
use Modules\Shared\TimeZone\Database\Seeders\TimeZoneCountrySeederTableSeeder;
use Modules\Shared\Privilege\Database\Seeders\PrivilegeModulesSeederTableSeeder;
use Modules\Shared\ProfessionalBodie\Database\Seeders\ProfessionalBodiessSeeder;
use Modules\Company\CompanyCore\Database\Seeders\CompanyModulesSeederTableSeeder;
use Modules\Shared\TypePrivilege\Database\Seeders\TypePrivilegeSeederTableSeeder;
use Modules\Shared\TypeAllowance\Database\Seeders\TypeAllowancesSeederTableSeeder;
use Modules\Shared\BankTypeAccount\Database\Seeders\MaritalStatusSeederTableSeeder;
use Modules\Shared\RightTerminate\Database\Seeders\RightTerminateSeederTableSeeder;
use Modules\Shared\BankTypeAccount\Database\Seeders\BankTypeAccountSeederTableSeeder;
use Modules\Shared\TypeWorkingHour\Database\Seeders\TypeWorkingHourSeederTableSeeder;
use Modules\Shared\Privilege\Database\Seeders\UpdatePrivilegeModulesSeederTableSeeder;
use Modules\Shared\AcademicQualification\Database\Seeders\AcademicQualificationSeederTableSeeder;
use Modules\Shared\AcademicSpecialization\Database\Seeders\AcademicSpecializationssSeederTableSeeder;
use Modules\Shared\AcademicSpecialization\Database\Seeders\AcademicSpecializationsNewSeederTableSeeder;
use Modules\Shared\AcademicSpecialization\Database\Seeders\AcademicSpecializationsUpdateSeederTableSeeder;
use Modules\Shared\MaritalStatus\Database\Seeders\MaritalStatusSeederTableSeeder as SeedersMaritalStatusSeederTableSeeder;

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
        $this->call(TimeZoneSeederTableSeeder::class);
        $this->call(LanguagesTableSeeder::class);
        $this->call(JobTitleModulesSeederTableSeeder::class);
        $this->call(AdminSeedTableSeeder::class);
        $this->call(CompanyModulesSeederTableSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);


        $this->call(SettingSeeder::class);
        $this->call(DriverTableSeeder::class);
        $this->call(QuestionSettingTableSeeder::class);
        $this->call(DefaultIdentifierSeederTableSeeder::class);

        $this->call(DefaultLoginWaySeederTableSeeder::class);

        $this->call(BanksModulesSeederTableSeeder::class);

        $this->call(AcademicQualificationSeederTableSeeder::class);

        $this->call(AcademicSpecializationssSeederTableSeeder::class);

        $this->call(UniversitiesSeederTableSeeder::class);
        $this->call(ProfessionalBodiessSeeder::class);
        $this->call(PrivilegeModulesSeederTableSeeder::class);

        $this->call(PeriodSeederTableSeeder::class);
        $this->call(TypeAllowancesSeederTableSeeder::class);
        $this->call(TypePrivilegeSeederTableSeeder::class);

        $this->call(TimeZoneCountrySeederTableSeeder::class);

        $this->call(JobTypeSeederTable::class);

        $this->call(SalaryTypeSeederTableSeeder::class);
        $this->call(TimeUnitsSeederTableSeeder::class);

        $this->call(NatureWorkSeederTableSeeder::class);
        $this->call(RightTerminateSeederTableSeeder::class);
        $this->call(TypeWorkingHourSeederTableSeeder::class);

        $this->call(ProgramDatabaseSeeder::class);
        $this->call(RegistrationFormsSeeder::class);
        $this->call(SubEntityDatabaseSeeder::class);
        $this->call(BankTypeAccountSeederTableSeeder::class);
        $this->call(SeedersMaritalStatusSeederTableSeeder::class);
        $this->call(UpdatePrivilegeModulesSeederTableSeeder::class);
        $this->call(AcademicSpecializationsUpdateSeederTableSeeder::class);
        $this->call(AcademicSpecializationsNewSeederTableSeeder::class);
        $this->call(ModuleDatabaseSeeder::class);
        $this->call(FeatureDatabaseSeeder::class);
    }
}
