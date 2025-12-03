<?php

namespace Modules\WebsiteCMS\WebsiteThemeSetting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Models\Company;
use Modules\WebsiteCMS\WebsiteThemeSetting\Models\WebsiteThemeSetting;
use function Symfony\Component\Translation\t;

class AssignDefaultThemeToCompaniesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This should be called in TenantDatabaseSeeder to assign default theme to all companies
     */
    public function run(): void
    {
        // Get the default theme
        $defaultTheme = WebsiteThemeSetting::where('is_default', true)->first();

        if (!$defaultTheme) {
            $this->command->warn('No default theme found. Please run DefaultWebsiteThemeSettingSeeder first.');
            return;
        }

        // Get all companies
        $companyId = tenant("id");

        if (!$companyId) {
            $this->command->error('No tenant context found. This seeder must run within a tenant context.');
            return;
        }



        $existingAssignment = DB::table('company_website_theme_settings')
            ->where('company_id', $companyId)
            ->exists();

        if (!$existingAssignment) {
            // Assign default theme to company
            DB::table('company_website_theme_settings')->insert([
                'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'company_id' => $companyId,
                'website_theme_setting_id' => $defaultTheme->id,
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        }


    }
}
