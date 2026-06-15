<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\CompanyPackageAssignmentSeeder;
use Database\Seeders\ArchiveLibraryStorageLimitSeeder;
use Database\Seeders\ArchiveLibraryFolderLimitSeeder;
use Modules\ArchiveLibrary\Folder\Database\Seeders\OfficialDocumentsFolderSeeder;
use Modules\DocumentType\Database\Seeders\DocumentTypeSeederTableSeeder;
use Modules\NotificationSettings\Database\seeders\DefaultNotificationSettingsSeeder;
use Modules\Project\ProjectType\Database\Seeders\ProjectTypeSeeder;
use Modules\Project\ProjectType\Database\Seeders\SchemaSeeder;
use Modules\Setting\Database\Seeders\DriverTableSeeder;
use Modules\Setting\Database\Seeders\QuestionSettingTableSeeder;
use Modules\Setting\Database\Seeders\DefaultLoginWaySeederTableSeeder;
use Modules\JobTitle\Database\Seeders\JobTitleModulesSeederTableSeeder;
use Modules\Setting\Database\Seeders\DefaultIdentifierSeederTableSeeder;
use Modules\User\Database\Seeders\GenaralAdminSeedTableSeeder;
use Modules\Leave\LeavePolicy\Database\Seeders\LeavePolicySeeder;
use Modules\Leave\LeaveType\Database\Seeders\LeaveTypeBranchSeeder;
use Modules\WebsiteCMS\WebsiteContactInfo\Database\Seeders\WebsiteContactInfoSeeder;
use Modules\WebsiteCMS\WebsiteOurService\Database\Seeders\WebsiteOurServiceSeeder;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Database\Seeders\WebsiteTermsAndConditionSeederTableSeeder;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Database\Seeders\WebsiteHomePageSettingSeeder;
use Modules\WebsiteCMS\WebsiteAboutUs\Database\Seeders\WebsiteAboutUsSeeder;
use Modules\WebsiteCMS\WebsiteTheme\Database\Seeders\WebsiteThemeSeeder;
use Modules\WebsiteCMS\WebsiteThemeSetting\Database\Seeders\AssignDefaultThemeToCompaniesSeeder;
use Modules\ProcedureSetting\Database\Seeders\InternalProcedureSettingsSeeder;
use Modules\ProcedureSetting\Database\Seeders\WorkFlowForBranchesSeeder;

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

        // Set default storage limit for archive library (1000 MB for files, 1000 folders)
        $this->call(ArchiveLibraryStorageLimitSeeder::class);
        $this->call(ArchiveLibraryFolderLimitSeeder::class);
        $this->call(OfficialDocumentsFolderSeeder::class);

        $this->call(JobTitleModulesSeederTableSeeder::class);
        $this->call(SettingSeeder::class);
        $this->call(DriverTableSeeder::class);
        $this->call(QuestionSettingTableSeeder::class);
        $this->call(DefaultIdentifierSeederTableSeeder::class);

        $this->call(DefaultLoginWaySeederTableSeeder::class);

        // Create default Annual Year leave policy for new companies
        $this->call(LeavePolicySeeder::class);
        $this->call(DocumentTypeSeederTableSeeder::class);

        $this->call(DefaultNotificationSettingsSeeder::class);

        $this->call(WebsiteTermsAndConditionSeederTableSeeder::class);


        // Create default website contact info for the company
        $this->call(WebsiteContactInfoSeeder::class);

        // Create default website home page settings for the company
        $this->call(WebsiteHomePageSettingSeeder::class);
        // Create default website about us for the company
        $this->call(WebsiteAboutUsSeeder::class);

        // Create default website theme and color palettes for the company
        $this->call(WebsiteThemeSeeder::class);

        // Assign default theme setting to the company
        $this->call(AssignDefaultThemeToCompaniesSeeder::class);
        $this->call(WebsiteOurServiceSeeder::class);
        $this->call(ProjectTypeSeeder::class);
        $this->call(SchemaSeeder::class);

        $this->call(WorkFlowForBranchesSeeder::class);
        $this->call(InternalProcedureSettingsSeeder::class);

//        $this->call(MainPackageSeeder::class);
    }
}
