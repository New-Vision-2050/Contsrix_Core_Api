<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Company\BusinessType\Database\Seeders\BusinessTypeSeederTableSeeder;
use Modules\DocumentType\Database\Seeders\DocumentTypeSeederTableSeeder;
use Modules\Leave\LeavePolicy\Database\Seeders\LeavePolicySeeder;
use Modules\NotificationSettings\Database\seeders\DefaultNotificationSettingsSeeder;
use Modules\Project\ProjectManagement\Database\Seeders\ProjectPermissionsSeeder;
use Modules\Project\TermServices\Database\Seeders\TermServicesSeeder;
use Modules\ClientRequest\Database\Seeders\ClientRequestSeeder;
use Modules\Shared\Bank\Database\Seeders\BanksOtherModulesSeederTableSeeder;
use Modules\Shared\Bank\Database\Seeders\MoroccanBanksSeeder;
use Modules\Shared\University\Database\Seeders\MoroccanUniversitiesSeeder;
use Modules\Shared\University\Database\Seeders\UniversitiesOtherSeederTableSeeder;
use Modules\SubscriptionSystem\Modules\Database\Seeders\ModuleStructureSeeder;
use Modules\UserInfo\ContractualRelationship\Database\Seeders\ContractualRelationshipTypeSeeder;
use Modules\WebsiteCMS\WebsiteThemeSetting\Database\Seeders\DefaultWebsiteThemeSettingSeeder;
use Modules\WebsiteCMS\WebsiteThemeSetting\Models\WebsiteThemeSettingDepartment;
use Ranium\SeedOnce\Traits\SeedOnce;
use Modules\Setting\Database\Seeders\DriverTableSeeder;
use Modules\User\Database\Seeders\AdminSeedTableSeeder;
use Modules\Program\Database\Seeders\ProgramDatabaseSeeder;
use Modules\Country\Database\Seeders\CountrySeederTableSeeder;
use Modules\Shared\JobType\Database\Seeders\JobTypeSeederTable;
use Modules\SubEntity\Database\Seeders\RegistrationFormsSeeder;
use Modules\SubEntity\Database\Seeders\SubEntityDatabaseSeeder;
use Modules\Setting\Database\Seeders\QuestionSettingTableSeeder;
use Modules\Shared\Currency\Database\Seeders\CurrencySeederTable;
use Modules\Shared\Language\Database\Seeders\LanguagesTableSeeder;
use Modules\Shared\Period\Database\Seeders\PeriodSeederTableSeeder;
use Modules\Subscription\Module\Database\Seeder\ModuleDatabaseSeeder;
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
use Modules\Ecommerce\Warehous\Database\Seeders\WarehousSeederTableSeeder;
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
use Modules\Shared\Installment\Database\Seeders\InstallmentModulesSeederTableSeeder;
use Modules\Shared\MaritalStatus\Database\Seeders\MaritalStatusSeederTableSeeder as SeedersMaritalStatusSeederTableSeeder;
use Modules\Shared\Payment\Database\Seeders\PaymentModulesSeederTableSeeder;
use Modules\Shared\SocialIcon\Database\Seeders\SocialIconsModulesSeederTableSeeder;
use Modules\Shared\Payment\Models\Payment;
use Modules\UserInfo\UserProfessionalData\Database\seeders\SyncUserIdsInUserProfessionalDataSeeder;
use Modules\Unit\Database\Seeders\UnitSeederTableSeeder;
use Modules\Shared\PaymentMethodData\Database\Seeders\PaymentMethodDataSeeder;

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
        $this->call(BankTypeAccountSeederTableSeeder::class);
        $this->call(SeedersMaritalStatusSeederTableSeeder::class);
        $this->call(UpdatePrivilegeModulesSeederTableSeeder::class);
        $this->call(AcademicSpecializationsUpdateSeederTableSeeder::class);
        $this->call(AcademicSpecializationsNewSeederTableSeeder::class);
        $this->call(BanksOtherModulesSeederTableSeeder::class);
        $this->call(UniversitiesOtherSeederTableSeeder::class);
        $this->call(MainPackageSeeder::class);

        $this->call(SubEntityDatabaseSeeder::class);


        $this->call(MoroccanUniversitiesSeeder::class);
        $this->call(MoroccanBanksSeeder::class);
        $this->call(SyncUserIdsInUserProfessionalDataSeeder::class);

        $this->call(WarehousSeederTableSeeder::class);

        $this->call(LeavePolicySeeder::class);

        $this->call(PublicHolidaysTableSeeder::class);
        $this->call(PaymentModulesSeederTableSeeder::class);
        $this->call(InstallmentModulesSeederTableSeeder::class);
        $this->call(DocumentTypeSeederTableSeeder::class);

        $this->call(DefaultNotificationSettingsSeeder::class);
        $this->call(UnitSeederTableSeeder::class);
        $this->call(SocialIconsModulesSeederTableSeeder::class);
        $this->call(PaymentMethodDataSeeder::class);
        $this->call(DefaultWebsiteThemeSettingSeeder::class);
        $this->call(ContractualRelationshipTypeSeeder::class);
        $this->call(TermServicesSeeder::class);
        $this->call(ClientRequestSeeder::class);
        $this->call(ProfessionalDegreesSeeder::class);
        $this->call(ProjectPermissionsSeeder::class);
        $this->call(ProjectShareTypeSeeder::class);

    }
}
